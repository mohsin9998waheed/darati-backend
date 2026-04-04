<?php
/**
 * S3Service Integration Test
 * Run: php s3_test.php
 *
 * Tests the exact code path used by the app controllers.
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\S3Service;
use Illuminate\Support\Facades\Facade;

Facade::setFacadeApplication($app);

$s3 = null;

// ─── 1. Construct S3Service ────────────────────────────────────────────────
echo "\n=== 1. Constructing S3Service ===\n";
try {
    $s3 = new S3Service();
    echo "OK — S3Service created successfully\n";
} catch (\Throwable $e) {
    echo "FAILED — " . get_class($e) . ": " . $e->getMessage() . "\n";
    exit(1);
}

// ─── 2. Upload a small test file ─────────────────────────────────────────
echo "\n=== 2. Uploading test file to thumbnails/ ===\n";
$tmpPath  = tempnam(sys_get_temp_dir(), 's3test_') . '.jpg';
// Create a minimal valid JPEG (1x1 pixel)
file_put_contents($tmpPath, base64_decode(
    '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkS'
    . 'Ew8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBD'
    . 'AQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy'
    . 'MjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACf/'
    . 'EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/'
    . 'aAAwDAQACEQMRAD8AJQAB/9k='
));

try {
    $fakeFile = new \Illuminate\Http\UploadedFile($tmpPath, 's3test.jpg', 'image/jpeg', null, true);
    $key      = $s3->upload($fakeFile, 'thumbnails');
    echo "OK — Uploaded to key: {$key}\n";
} catch (\Throwable $e) {
    echo "FAILED — " . get_class($e) . ": " . $e->getMessage() . "\n";
    @unlink($tmpPath);
    exit(1);
}

@unlink($tmpPath);

// ─── 3. Check it exists ────────────────────────────────────────────────────
echo "\n=== 3. Checking exists() ===\n";
$exists = $s3->exists($key);
echo ($exists ? "OK — exists: yes\n" : "FAILED — exists: no (upload may have silently failed)\n");

// ─── 4. Get size ───────────────────────────────────────────────────────────
echo "\n=== 4. Get size ===\n";
$size = $s3->getSize($key);
echo "OK — size: {$size} bytes\n";

// ─── 5. Generate signed URL (48 h) ────────────────────────────────────────
echo "\n=== 5. Generating signed URL (48 hours) ===\n";
try {
    $signedUrl = $s3->temporaryUrl($key, 60 * 48);
    echo "OK — signed URL generated\n";
    echo "URL (first 120 chars): " . substr($signedUrl, 0, 120) . "...\n";
} catch (\Throwable $e) {
    echo "FAILED — " . get_class($e) . ": " . $e->getMessage() . "\n";
}

// ─── 6. Delete test object ────────────────────────────────────────────────
echo "\n=== 6. Deleting test object ===\n";
try {
    $s3->delete($key);
    echo "OK — deleted\n";
} catch (\Throwable $e) {
    echo "FAILED — " . $e->getMessage() . "\n";
}

// ─── 7. Verify hasFile behaviour via raw config ───────────────────────────
echo "\n=== 7. Config check ===\n";
$cfg = config('filesystems.disks.public');
echo "bucket  : " . ($cfg['bucket'] ?? '(null)') . "\n";
echo "region  : " . ($cfg['region'] ?? '(null)') . "\n";
echo "key len : " . strlen($cfg['key'] ?? '') . " chars\n";
echo "secret  : " . (strlen($cfg['secret'] ?? '') > 5 ? '***set***' : '(empty!)') . "\n";

echo "\n=== ALL DONE ===\n";
