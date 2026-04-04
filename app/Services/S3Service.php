<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * AWS SDK-based S3 service that never sends ACL headers.
 * This is required when the bucket has "Object Ownership = Bucket owner enforced" (ACLs disabled).
 * Public read access is controlled by bucket policy, not object ACLs.
 */
class S3Service
{
    private S3Client $client;
    private string $bucket;
    private string $baseUrl;

    public function __construct()
    {
        $disk = config('filesystems.disks.public');

        $this->bucket  = $disk['bucket'];
        $this->baseUrl = rtrim($disk['url'] ?? "https://{$this->bucket}.s3.amazonaws.com", '/');

        $this->client = new S3Client([
            'version'     => 'latest',
            'region'      => $disk['region'],
            'credentials' => [
                'key'    => $disk['key'],
                'secret' => $disk['secret'],
            ],
        ]);
    }

    /**
     * Upload a file to S3 without ACL.
     * Returns the S3 key (e.g. "thumbnails/uuid.jpg").
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $ext  = strtolower($file->getClientOriginalExtension());
        $key  = $folder . '/' . Str::uuid() . '.' . $ext;
        $mime = $file->getMimeType() ?? 'application/octet-stream';

        $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'Body'        => fopen($file->getRealPath(), 'rb'),
            'ContentType' => $mime,
        ]);

        return $key;
    }

    /**
     * Upload raw content string to S3 without ACL.
     */
    public function put(string $key, string $content, string $contentType = 'text/plain'): string
    {
        $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'Body'        => $content,
            'ContentType' => $contentType,
        ]);

        return $key;
    }

    /**
     * Delete an object from S3.
     */
    public function delete(?string $key): void
    {
        if (! $key) {
            return;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
        } catch (\Throwable) {
            // Non-fatal: already deleted or key doesn't exist
        }
    }

    /**
     * Generate a public URL for the given key.
     * Only works if the bucket policy allows s3:GetObject for this prefix.
     */
    public function url(string $key): string
    {
        return $this->baseUrl . '/' . ltrim($key, '/');
    }

    /**
     * Generate a pre-signed URL (for private objects like audio files).
     */
    public function temporaryUrl(string $key, int $minutes = 60): string
    {
        $cmd     = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ]);
        $request = $this->client->createPresignedRequest($cmd, "+{$minutes} minutes");

        return (string) $request->getUri();
    }

    /**
     * Check if a key exists.
     */
    public function exists(string $key): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
            return true;
        } catch (\Aws\Exception\AwsException) {
            return false;
        }
    }

    /**
     * Get file size in bytes.
     */
    public function getSize(string $key): int
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
            return (int) ($result['ContentLength'] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Open a readable PHP stream for the given key (used for audio streaming).
     * Supports Range requests efficiently via AWS SDK streaming.
     *
     * @param int $start  Byte offset to start reading from
     * @param int $end    Byte offset to stop reading at (inclusive), 0 = until end
     * @return resource
     */
    public function readStream(string $key, int $start = 0, int $end = 0)
    {
        $params = [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ];

        if ($start > 0 || $end > 0) {
            $rangeEnd = $end > 0 ? $end : '';
            $params['Range'] = "bytes={$start}-{$rangeEnd}";
        }

        $result = $this->client->getObject($params);

        return $result['Body']->detach();
    }
}
