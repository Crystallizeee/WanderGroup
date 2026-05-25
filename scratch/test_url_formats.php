<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Test different URL formats for the image
$fileId = '16AEisk4pxB1ASxEte7Hkvwda4pfla2bD';

$urls = [
    "uc?export=media" => "https://drive.google.com/uc?id={$fileId}&export=media",
    "uc?export=download" => "https://drive.google.com/uc?id={$fileId}&export=download",
    "thumbnail sz=w800" => "https://drive.google.com/thumbnail?id={$fileId}&sz=w800",
    "lh3 googleusercontent" => "https://lh3.googleusercontent.com/d/{$fileId}",
];

foreach ($urls as $label => $url) {
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'follow_location' => false]]);
    $headers = @get_headers($url, true, $ctx);
    $status = $headers[0] ?? 'no response';
    $location = $headers['Location'] ?? $headers['location'] ?? '-';
    $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '-';
    echo "[$label]" . PHP_EOL;
    echo "  URL: $url" . PHP_EOL;
    echo "  Status: $status" . PHP_EOL;
    echo "  Content-Type: " . (is_array($contentType) ? end($contentType) : $contentType) . PHP_EOL;
    echo "  Redirect: " . (is_array($location) ? end($location) : $location) . PHP_EOL;
    echo PHP_EOL;
}
