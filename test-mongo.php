<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getMongoConnection();
    echo "Successfully connected to MongoDB! Database: " . $db->getDatabaseName() . "\n";
    
    // Test collection
    $collection = $db->media_assets;
    echo "Using collection: " . $collection->getCollectionName() . "\n";
    
    // Test insert
    $testDoc = [
        'test' => true,
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ];
    
    $result = $collection->insertOne($testDoc);
    echo "Inserted test document with ID: " . $result->getInsertedId() . "\n";
    
    // Test find
    $found = $collection->findOne(['_id' => $result->getInsertedId()]);
    echo "Found test document: " . json_encode($found) . "\n";
    
    // Clean up
    $collection->deleteOne(['_id' => $result->getInsertedId()]);
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your MongoDB connection settings in config/database.php\n";
}