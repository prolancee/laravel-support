<?php

namespace PROLANCEE\Support\Classes\Config;

final class AppMetaData
{
    /**
     * Get the app version details.
     *
     */
    public static function getVersion(): array
    {
        return [
            'phpVersion'     => phpversion(),
            'laravelVersion' => app()->version(),
            'pkgVersion'     => '1.0.0',
        ];
    }

    /**
     * Get the app configuration details.
     *
     */
    public static function getConfig(): array
    {
        return [
            'locale'       => config('app.locale'),
            'configCached' => app()->configurationIsCached() ? 'true' : 'false',
            'appDebug'     => config('app.debug') ? 'true' : 'false',
            'appEnv'       => config('app.env'),
        ];
    }

    /**
     * Get start point: base url.
     *
     */
    public static function getStartPoint(array $separate, string $root): string
    {
        return ($separate['start'] ?? '')
            . "prolancee/{$root}"
            . ($separate['end'] ?? '');
    }
}
