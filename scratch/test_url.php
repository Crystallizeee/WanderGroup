<?php
$path = 'https://drive.google.com/uc?id=16AEisk4pxB1ASxEte7Hkvwda4pfla2bD&export=media';

// Test original regex
preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $path, $m1);
echo "Regex 1 (/file/d/): " . ($m1[1] ?? 'NOT_FOUND') . PHP_EOL;

preg_match('/id=([a-zA-Z0-9_-]+)/', $path, $m2);
echo "Regex 2 (id=): " . ($m2[1] ?? 'NOT_FOUND') . PHP_EOL;

// Parse as URL
$query = parse_url($path, PHP_URL_QUERY);
parse_str($query, $params);
echo "URL parse id: " . ($params['id'] ?? 'NOT_FOUND') . PHP_EOL;

// The simple solution: uc?id= URL can be used DIRECTLY as img src
// Google Drive uc?export=media should work for direct embed if accessible
echo "Direct URL test: " . $path . PHP_EOL;
echo "Thumbnail URL: https://drive.google.com/thumbnail?id=" . ($params['id'] ?? '') . "&sz=w800" . PHP_EOL;
