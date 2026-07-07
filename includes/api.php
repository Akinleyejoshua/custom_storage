<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

// Set JSON content type
header('Content-Type: application/json');

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

// Exception handler
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'details' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
});

// API endpoint router
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$path = parse_url($requestUri, PHP_URL_PATH);
$path = substr($path, strlen($basePath));

// Route API requests
if (strpos($path, '/api/assets') === 0) {
    $collection = getMediaCollection();
    
    // Upload endpoint
    if ($requestMethod === 'POST' && $path === '/api/assets/upload') {
        handleUpload($collection);
    }
    // List assets endpoint
    elseif ($requestMethod === 'GET' && $path === '/api/assets') {
        handleListAssets($collection);
    }
    // Single asset endpoint
    elseif ($requestMethod === 'GET' && preg_match('/^\/api\/assets\/([a-f0-9]{24})$/', $path, $matches)) {
        handleGetAsset($collection, $matches[1]);
    }
    // Delete asset endpoint
    elseif ($requestMethod === 'DELETE' && preg_match('/^\/api\/assets\/([a-f0-9]{24})$/', $path, $matches)) {
        handleDeleteAsset($collection, $matches[1]);
    }
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}

// Handle file upload
function handleUpload($collection) {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded or upload error']);
            return;
        }
        
        $file = $_FILES['file'];
        
        // Validate file type
        if (!isAllowedMimeType($file['type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'File type not allowed']);
            return;
        }
        
        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            http_response_code(413);
            echo json_encode(['error' => 'File too large']);
            return;
        }
        
        // Generate secure filename
        $secureFilename = generateSecureFilename($file['name']);
        $storagePath = UPLOAD_DIR . '/' . $secureFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file']);
            return;
        }
        
        // Create asset record
        $assetId = new MongoDB\BSON\ObjectId();
        $publicUrl = BASE_URL . '/public/assets/' . $secureFilename;
        
        $asset = [
            '_id' => $assetId,
            'assetId' => (string) $assetId,
            'originalFilename' => $file['name'],
            'secureFilename' => $secureFilename,
            'mimeType' => $file['type'],
            'fileSize' => $file['size'],
            'storagePath' => $storagePath,
            'publicUrl' => $publicUrl,
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ];
        
        // Insert into database
        $result = $collection->insertOne($asset);
        
        if (!$result->isAcknowledged()) {
            // Clean up file if database insert failed
            unlink($storagePath);
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save asset metadata']);
            return;
        }
        
        // Return success response
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => formatAsset($asset)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Upload failed', 'details' => $e->getMessage()]);
    }
}

// Handle list assets
function handleListAssets($collection) {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $skip = ($page - 1) * $limit;
        
        $total = $collection->countDocuments();
        $assets = $collection->find([], [
            'skip' => $skip,
            'limit' => $limit,
            'sort' => ['createdAt' => -1]
        ]);
        
        $formattedAssets = [];
        foreach ($assets as $asset) {
            $formattedAssets[] = formatAsset($asset);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $formattedAssets,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve assets', 'details' => $e->getMessage()]);
    }
}

// Handle get single asset
function handleGetAsset($collection, $assetId) {
    try {
        $asset = $collection->findOne(['assetId' => $assetId]);
        
        if (!$asset) {
            http_response_code(404);
            echo json_encode(['error' => 'Asset not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => formatAsset($asset)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve asset', 'details' => $e->getMessage()]);
    }
}

// Handle delete asset
function handleDeleteAsset($collection, $assetId) {
    try {
        $asset = $collection->findOne(['assetId' => $assetId]);
        
        if (!$asset) {
            http_response_code(404);
            echo json_encode(['error' => 'Asset not found']);
            return;
        }
        
        // Delete file from storage
        if (file_exists($asset['storagePath'])) {
            unlink($asset['storagePath']);
        }
        
        // Delete from database
        $result = $collection->deleteOne(['assetId' => $assetId]);
        
        if ($result->getDeletedCount() === 0) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete asset']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Asset deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete asset', 'details' => $e->getMessage()]);
    }
}

// Format asset for JSON response
function formatAsset($asset) {
    $formatted = [];
    
    // Convert MongoDB types to JSON-serializable types
    foreach ($asset as $key => $value) {
        if ($value instanceof MongoDB\BSON\ObjectId) {
            $formatted[$key] = (string) $value;
        } elseif ($value instanceof MongoDB\BSON\UTCDateTime) {
            $formatted[$key] = $value->toDateTime()->format('Y-m-d\TH:i:s.v\Z');
        } else {
            $formatted[$key] = $value;
        }
    }
    
    // Remove storagePath for security
    unset($formatted['storagePath']);
    
    return $formatted;
}