<?php
$listUrl = 'https://izeltas.com.tr/list-files.php';
$downloadBase = 'https://izeltas.com.tr/download.php?file=';
$saveDir = __DIR__ . '/downloaded_files';

echo "Fetching file list from: $listUrl\n";

// Fetch the list page content
$html = curlGet($listUrl);
if ($html === false) {
    die("[ERROR] Failed to fetch file list from $listUrl\n");
}

// Split content by the exact separator "**--seperate--**"
$parts = explode('**--seperate--**', $html);

// Trim and filter empty entries
$files = array_filter(array_map('trim', $parts));

$total = count($files);
echo "Found $total files to download.\n";

$successCount = 0;
$failures = [];

foreach ($files as $fileParamRaw) {
    // The $fileParamRaw might be a full URL or just the encoded path, clean it:

    // If it’s a full URL containing download.php?file=, extract only the file param part
    if (preg_match('#download\.php\?file=([^"\']+)#', $fileParamRaw, $m)) {
        $fileParam = $m[1];
    } else {
        // Otherwise assume it’s already the encoded file path
        $fileParam = $fileParamRaw;
    }

    if (trim($fileParam) === '' || substr($fileParam, -1) === '/') {
        echo "[SKIP] Skipping folder or empty path: $fileParam\n";
        continue;
    }

    $downloadUrl = $downloadBase . $fileParam;
    $savePath = $saveDir . '/' . urldecode($fileParam);
    $dirPath = dirname($savePath);

    // Special case for dot-files (.htaccess, .DS_Store)
    $baseName = basename($savePath);
    if (in_array($baseName, ['.htaccess', '.DS_Store'])) {
        // Just ensure parent directory exists, no mkdir on dot-files
        if (!is_dir($dirPath)) {
            if (!mkdir($dirPath, 0777, true)) {
                echo "[FAIL] Could not create directory for dot-file: $dirPath\n";
                $failures[] = $fileParam;
                continue;
            }
        }
    } else {
        // For normal files, create directory if missing
        if (!is_dir($dirPath)) {
            if (!mkdir($dirPath, 0777, true)) {
                echo "[FAIL] Could not create directory: $dirPath\n";
                $failures[] = $fileParam;
                continue;
            }
        }
    }

    echo "Downloading: $fileParam ... ";

    $data = curlGet($downloadUrl);
    if ($data === false || strlen($data) === 0) {
        echo "[FAIL] Empty response\n";
        $failures[] = $fileParam;
        continue;
    }

    if (@file_put_contents($savePath, $data) === false) {
        echo "[FAIL] Could not write file: $savePath\n";
        $failures[] = $fileParam;
        continue;
    }

    echo "[OK]\n";
    $successCount++;
}

echo "\nDownload complete.\n";
echo "Successfully downloaded: $successCount file(s).\n";

if (!empty($failures)) {
    echo "Failed to download:\n";
    foreach ($failures as $fail) {
        echo " - $fail\n";
    }
}

function curlGet(string $url): string|false {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; downloader/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
