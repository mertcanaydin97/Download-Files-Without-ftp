<?php
$baseDir = realpath(__DIR__ . '/'); // Same folder as above
$file = $_GET['file'] ?? '';
$realPath = realpath($baseDir . '/' . $file);

// Security check
if (!$realPath || strpos($realPath, $baseDir) !== 0 || !is_file($realPath)) {
    http_response_code(404);
    exit('File not found.');
}

// Send file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
header('Content-Length: ' . filesize($realPath));
readfile($realPath);
exit;
