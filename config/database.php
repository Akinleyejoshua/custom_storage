<?php
// MongoDB Connection URI
// Using the provided connection string
define('MONGODB_URI', 'mongodb+srv://test:test1@cluster0.3b2pwgu.mongodb.net/?appName=Cluster0');

define('MONGODB_DATABASE', 'media_gateway');

// MongoDB Connection
function getMongoConnection() {
    try {
        $client = new MongoDB\Client(MONGODB_URI);
        return $client->selectDatabase(MONGODB_DATABASE);
    } catch (Exception $e) {
        error_log("MongoDB connection error: " . $e->getMessage());
        die("Failed to connect to database");
    }
}

// File Storage Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500MB
define('BASE_URL', rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']), '/'));

// Allowed MIME types
function isAllowedMimeType($mimeType) {
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/tiff',
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'
    ];
    return in_array($mimeType, $allowedTypes);
}

// Generate secure filename
function generateSecureFilename($originalFilename) {
    $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $hash = bin2hex(random_bytes(16));
    $timestamp = time();
    return "{$timestamp}-{$hash}." . strtolower($ext);
}

// Format file size
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Get file content type
function getContentType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp',
        'tiff' => 'image/tiff',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska'
    ];
    return $mimeTypes[$ext] ?? 'application/octet-stream';
}

// Get MongoDB collection
function getMediaCollection() {
    $db = getMongoConnection();
    return $db->media_assets;
}
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>