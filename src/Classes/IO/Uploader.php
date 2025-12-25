<?php

namespace PROLANCEE\Support\Classes\IO;

use Illuminate\Http\UploadedFile;

final class Uploader
{
    /**
     * Handle multiple file uploads with type & size validation.
     */
    public static function handleFiles($files, string $folder = 'default', array $allowedTypes = [], int $maxSizeMB = 0)
    {
        if (!empty($allowedTypes)) {
            if (count($allowedTypes) === 1 && is_string($allowedTypes[0])) {
                $decoded = json_decode($allowedTypes[0], true);
                if (is_array($decoded)) {
                    $allowedTypes = $decoded;
                }
            }
        }

        $fileResults = [];

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) continue;

            // MIME type validation
            if (!empty($allowedTypes) && !in_array($file?->getMimeType(), $allowedTypes)) {
                $fileResults[] = [
                    'status'  => false,
                    'code'    => 415,
                    'message' => 'Invalid file type: ' . $file?->getMimeType(),
                    'file'    => [$file?->getClientOriginalName()],
                    'error'   => 'file_type',
                ];
                continue;
            }

            // Size validation
            if ($maxSizeMB > 0 && $file?->getSize() > $maxSizeMB) {
                $fileResults[] = [
                    'status'  => false,
                    'code'    => 413,
                    'message' => 'File exceeds max size of ' . $maxSizeMB . ' bytes.',
                    'file'    => [$file?->getClientOriginalName()],
                    'error'   => 'size',
                ];
                continue;
            }

            $fileHash  = sha1_file($file?->getRealPath());
            $folder    = trim($folder, "\"' ");
            $uploadDir = 'upload/' . $folder;

            // Check for duplicate
            $existingFile = self::findFileByHash($uploadDir, $fileHash);
            if ($existingFile) {
                $fileResults[] = [
                    'status'      => true,
                    'stored_path' => $existingFile,
                ];
                continue;
            }

            // Store file
            $fileName   = $fileHash . $index . '.' . $file?->getClientOriginalExtension();
            $storedPath = $file?->storeAs($uploadDir, $fileName, 'public');

            $fileResults[] = [
                'status'      => true,
                'stored_path' => $storedPath,
            ];
        }
        return $fileResults;
    }

    /**
     * Find file by its hash in the specified folder.
     */
    private static function findFileByHash(string $folder, string $hash)
    {
        $storagePath = storage_path('app/public/' . $folder);

        if (!is_dir($storagePath)) return false;

        $files = scandir($storagePath);
        foreach ($files as $file) {
            if (strpos($file, $hash) === 0) {
                return $folder . '/' . $file;
            }
        }
        return false;
    }
}