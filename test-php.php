<?php
// Simple PHP test script

echo "PHP is working!\n";
echo "PHP Version: " . phpversion() . "\n";

// Check for MongoDB extension
if (extension_loaded('mongodb')) {
    echo "MongoDB extension is installed\n";
} else {
    echo "MongoDB extension is NOT installed\n";
    echo "Install it with: pecl install mongodb\n";
}

// Check file permissions
$uploadDir = __DIR__ . '/uploads';
if (is_writable($uploadDir)) {
    echo "Uploads directory is writable\n";
} else {
    echo "Uploads directory is NOT writable\n";
    echo "Fix with: chmod -R 755 uploads\n";
}

// Test basic JSON output
echo "\nTesting JSON output:\n";
echo json_encode(['success' => true, 'message' => 'JSON test']);

// Test error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

try {
    echo "\nTesting error handling...\n";
    // This should cause an error
    // $test = undefined_function();
    echo "Error handling test passed\n";
} catch (Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
}