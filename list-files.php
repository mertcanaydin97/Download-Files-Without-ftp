<?php
$baseDir = __DIR__ . '/'; // Change this

function listFiles($dir, $base) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if($item != '.DS_Store'){
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . '/' . $item;
        if (is_dir($fullPath)) {
            listFiles($fullPath, $base);
        } else {
            $relativePath = ltrim(str_replace($base, '', $fullPath), '/\\');
            echo "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/download.php?file=" . urlencode($relativePath)."**--seperate--**";
        }}
    }
}

listFiles($baseDir, realpath($baseDir));
