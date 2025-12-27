<?php

namespace PROLANCEE\Support\Classes\Database;

use Exception;

final class DBJson
{
    /**
     * Prepares any array for database storage:
     * - Flattens nested arrays where needed
     * - Converts objects/arrays to JSON strings
     * - Handles inner JSON strings
     * - Preserves Unicode & slashes
     *
     * @param array $data
     * @return array
     */
    public static function prepareForDatabaseMinimal(array $data): array
    {
        $stack = [[$data, []]]; 
        $result = $data;

        while ($stack) {
            [$node, $path] = array_pop($stack);

            foreach ($node as $key => $values) {
                $currentPath = array_merge($path, [$key]);

                if (is_array($values) && self::isAssoc($values)) {
                    $stack[] = [$values, $currentPath];
                    continue;
                }

                if (is_array($values)) {
                    $flat = [];
                    foreach ($values as $v) {
                        if (is_string($v) && preg_match("/^\[.*\]$/s", $v)) {
                            $decoded = json_decode(str_replace("'", '"', $v), true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $dv) {
                                    $flat[] = (is_array($dv) || is_object($dv))
                                        ? json_encode($dv, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                        : $dv;
                                }
                                continue;
                            }
                        }
                        if (is_array($v) || is_object($v)) {
                            $flat[] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            continue;
                        }
                        if (is_string($v) && str_contains($v, ',')) {
                            $flat = array_merge($flat, array_map('trim', explode(',', $v)));
                            continue;
                        }

                        $flat[] = $v;
                    }
                    $ref = &$result;
                    foreach ($currentPath as $p) {
                        $ref = &$ref[$p];
                    }
                    $ref = count($flat) > 1
                        ? json_encode($flat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : ($flat[0] ?? '');
                }
            }
        }

        return $result;
    }

    /**
     * Convert saved DB string to minimal escaped JSON.
     *
     * @param string $savedString
     * @return string
     * @throws Exception
     */
    public static function makeMinimalEscapedJson(string $savedString): string
    {
        try {
            $outerArray = json_decode($savedString, true);

            if (!is_array($outerArray)) {
                throw new Exception("Invalid saved JSON string");
            }

            foreach ($outerArray as &$element) {
                if (is_string($element) && (str_starts_with($element, '{') || str_starts_with($element, '['))) {
                    $decoded = json_decode($element, true);
                    if ($decoded !== null) {
                        $element = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }
            unset($element);

            return json_encode($outerArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * Check if an array is associative
     */
    private static function isAssoc(array $arr): bool
    {
        return [] !== $arr && array_keys($arr) !== range(0, count($arr) - 1);
    }
}