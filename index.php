<?php
// Determine which page to serve
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$path = parse_url($requestUri, PHP_URL_PATH);
$path = substr($path, strlen($basePath));

// Route requests to appropriate HTML files
if ($path === '/' || $path === '') {
    include 'index.html';
} elseif ($path === '/upload') {
    include 'upload.html';
} elseif ($path === '/docs') {
    include 'docs.html';
} else {
    // Handle API requests
    include __DIR__ . '/../includes/api.php';
}