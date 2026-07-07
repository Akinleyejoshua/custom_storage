<?php
// Simple API test

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

try {
    $db = getMongoConnection();
    $collection = $db->media_assets;
    
    // Test basic API response
    echo json_encode([
        'success' => true,
        'message' => 'API is working!',
        'php_version' => phpversion(),
        'mongodb_extension' => extension_loaded('mongodb') ? 'Loaded' : 'Not loaded',
        'database' => $db->getDatabaseName(),
        'collection' => $collection->getCollectionName()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}