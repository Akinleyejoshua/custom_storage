<?php
require_once __DIR__ . '/../../config/database.php';

// Get filename from request
$filename = basename($_SERVER['REQUEST_URI']);
$filepath = UPLOAD_DIR . '/' . $filename;

// Check if file exists
if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Get file info
$filesize = filesize($filepath);
$filemtime = filemtime($filepath);
$fileext = pathinfo($filename, PATHINFO_EXTENSION);
$mimeType = getContentType($filename);

// Check for range requests
$range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;

// Send appropriate headers
header("Content-Type: $mimeType");
header("Content-Disposition: inline; filename=\"$filename\"");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $filemtime) . " GMT");
header("Etag: \"" . md5($filename . $filemtime . $filesize) . "\"");
header("Accept-Ranges: bytes");

// Handle range requests for video streaming
if ($range) {
    $fp = fopen($filepath, 'rb');
    
    // Parse range header
    $rangeParts = explode('=', $range, 2);
    $rangeParts = explode('-', $rangeParts[1], 2);
    $rangeStart = (int)$rangeParts[0];
    $rangeEnd = !empty($rangeParts[1]) ? (int)$rangeParts[1] : $filesize - 1;
    
    // Validate range
    if ($rangeStart >= $filesize) {
        header("HTTP/1.1 416 Requested Range Not Satisfiable");
        header("Content-Range: bytes */$filesize");
        exit;
    }
    
    if ($rangeEnd >= $filesize) {
        $rangeEnd = $filesize - 1;
    }
    
    $contentLength = $rangeEnd - $rangeStart + 1;
    
    // Send partial content headers
    header("HTTP/1.1 206 Partial Content");
    header("Content-Range: bytes $rangeStart-$rangeEnd/$filesize");
    header("Content-Length: $contentLength");
    
    // Output file content
    fseek($fp, $rangeStart);
    $remaining = $contentLength;
    while ($remaining > 0) {
        $chunkSize = min($remaining, 8192);
        echo fread($fp, $chunkSize);
        $remaining -= $chunkSize;
        flush();
    }
    
    fclose($fp);
} else {
    // Send full file
    header("Content-Length: $filesize");
    readfile($filepath);
}