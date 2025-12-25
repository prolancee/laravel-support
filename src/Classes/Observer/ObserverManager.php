<?php

namespace PROLANCEE\Support\Classes\Observer;

use Illuminate\Support\Facades\Storage;

final class ObserverManager
{
    protected const FILE_PATH = 'prolancee/blade/observer.gz';

    /**
     * Store observer DOM data as gzipped JSON.
     *
     * This method compresses the JSON payload containing
     * observer data, timestamp, and length, then saves
     * it to a file.
     *
     * @param string $observerData The raw observer data string.
     * @return void
     */
    public static function setObserver(string $observerData): bool
    {
        $payload = [
            'observer'  => $observerData,                     
            'stored_at' => now()->toIso8601String(),          
            'length'    => strlen($observerData),            
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $compressed = gzencode($json, 9);                    

        return Storage::put(self::FILE_PATH, $compressed);        
    }

    /**
     * Retrieve the observer DOM string from compressed file.
     *
     * This method reads the gzipped JSON file,
     * decompresses it, decodes the JSON, and
     * returns the 'observer' string inside it.
     *
     * @return string|null The observer data string or null if not found.
     */
    public static function getObserver(): ?string
    {
        if (!Storage::exists(self::FILE_PATH)) {
            return null;
        }

        $compressed = Storage::get(self::FILE_PATH);
        $json = gzdecode($compressed);
        $data = json_decode($json, true);

        return $data['observer'] ?? null;
    }

    /**
     * Retrieve the full metadata payload (decompressed).
     *
     * This method returns the entire decompressed
     * JSON payload as an associative array.
     *
     * @return array|null The full data array or null if file missing.
     */
    public static function getObserverMeta(): ?array
    {
        if (!Storage::exists(self::FILE_PATH)) {
            return null;
        }

        $compressed = Storage::get(self::FILE_PATH);
        $json = gzdecode($compressed);

        return json_decode($json, true);
    }

    /**
     * Retrieve the raw compressed metadata (gzipped JSON) as base64 encoded string.
     *
     * This method returns the gzipped binary data
     * base64-encoded for safe transport over JSON or HTTP.
     *
     * @return string|null Base64 encoded compressed data or null if missing.
     */
    public static function getObserverCompressed(): ?string
    {
        if (!Storage::exists(self::FILE_PATH)) {
            return null;
        }

        $compressed = Storage::get(self::FILE_PATH);
        return $compressed ? base64_encode($compressed) : null;
    }

    /**
     * Delete the observer metadata file from storage.
     *
     * @return bool True if file was deleted or did not exist, false if deletion failed.
     */
    public static function destroyObserver(): bool
    {
        if (!Storage::exists(self::FILE_PATH)) {
            return true;
        }

        return Storage::delete(self::FILE_PATH);
    }
}
