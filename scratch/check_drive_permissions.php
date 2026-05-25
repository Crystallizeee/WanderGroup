<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_DRIVE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_DRIVE_CLIENT_SECRET']);
$client->refreshToken($_ENV['GOOGLE_DRIVE_REFRESH_TOKEN']);
$client->addScope(Google\Service\Drive::DRIVE);

$service = new Google\Service\Drive($client);

$fileId = '16AEisk4pxB1ASxEte7Hkvwda4pfla2bD';

// Check current permissions
try {
    $file = $service->files->get($fileId, ['fields' => 'id,name,webViewLink,webContentLink,shared,permissions']);
    echo "File name: " . $file->getName() . PHP_EOL;
    echo "Shared: " . ($file->getShared() ? 'Yes' : 'No') . PHP_EOL;
    echo "webViewLink: " . $file->getWebViewLink() . PHP_EOL;
    echo "webContentLink: " . $file->getWebContentLink() . PHP_EOL;
    
    $perms = $service->permissions->listPermissions($fileId, ['fields' => 'permissions(id,type,role)']);
    echo "Permissions:" . PHP_EOL;
    foreach ($perms->getPermissions() as $perm) {
        echo "  type=" . $perm->getType() . " role=" . $perm->getRole() . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

// Try to make it public
try {
    $permission = new Google\Service\Drive\Permission();
    $permission->setType('anyone');
    $permission->setRole('reader');
    $service->permissions->create($fileId, $permission);
    echo "Made public: anyone with link can read" . PHP_EOL;
} catch (Exception $e) {
    echo "Cannot set public: " . $e->getMessage() . PHP_EOL;
}
