<?php

namespace PROLANCEE\Support\Classes\Routes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

final class RouterTracker
{
    protected const FILE_PATH_ROUTES = 'prolancee/routes.json';

    // Define allowed HTTP methods for validation and default structure
    protected const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];

    /**
     * Stores a route URI under a specific prefix (e.g.'api', 'web') into routes.json.
     *
     * @param string $request_method The HTTP method (GET, POST, etc.).
     * @param string $route_uri The route URI to store.
     * @param string $prefix The route category ('api', 'web').
     * @return void
     */
    public static function setRoute(string $request_method, string $route_uri, string $prefix): void
    {
        $request_method = strtoupper($request_method);

        if (!in_array($request_method, self::ALLOWED_METHODS)) {
            return;
        }
        $escapedRoute = str_replace('/', '\\/', $route_uri);

        $newData = [
            'routes' => [],
            'stored_at' => now()->toIso8601String()
        ];

        foreach ([$prefix] as $defaultPrefix) {
            $newData['routes'][$defaultPrefix] = array_fill_keys(self::ALLOWED_METHODS, []);
        }

        if (Storage::exists(self::FILE_PATH_ROUTES)) {
            $existingJson = Storage::get(self::FILE_PATH_ROUTES);
            $decoded = json_decode($existingJson, true);
            if (is_array($decoded)) {
                $newData = array_merge_recursive($newData, $decoded);
            }
        }

        if (!isset($newData['routes'][$prefix])) {
            $newData['routes'][$prefix] = array_fill_keys(self::ALLOWED_METHODS, []);
        }

        if (!isset($newData['routes'][$prefix][$request_method])) {
            $newData['routes'][$prefix][$request_method] = [];
        }

        if (!in_array($escapedRoute, $newData['routes'][$prefix][$request_method])) {
            $newData['routes'][$prefix][$request_method][] = $escapedRoute;
            $newData['stored_at'] = now()->toIso8601String();

            Storage::put(self::FILE_PATH_ROUTES, json_encode($newData, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Retrieve stored routes by method and prefix or all routes.
     *
     * @param string|null $request_method The HTTP method (GET, POST, etc.). Optional.
     * @param string|null $prefix The route category ('api', 'web'). Optional.
     * @return array
     */
    public static function getRoutes(string $request_method = null, string $prefix = null): array
    {
        if (! Storage::exists(self::FILE_PATH_ROUTES)) {
            return [];
        }

        $data = json_decode(Storage::get(self::FILE_PATH_ROUTES), true);
        if (!is_array($data) || !isset($data['routes'])) {
            return [];
        }

        $routes = $data['routes'];

        if ($prefix === null && $request_method === null) {
            return $routes;
        }

        if ($prefix !== null && $request_method === null) {
            return $routes[$prefix] ?? [];
        }

        if ($prefix !== null && $request_method !== null) {
            return $routes[$prefix][$request_method] ?? [];
        }

        return [];
    }

    /**
     * Track the current HTTP request route and store it.
     *
     * Captures the request method (GET, POST, PUT, PATCH, DELETE) and 
     * the requested URI without query parameters, then stores the 
     * normalized route using setRoute().
     *
     * @return void
     */
    public static function trackRoute( string $prefix = 'client'): void
    {
        $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (!in_array($method, $allowedMethods, true)) {
            return; 
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? null;

        if ($requestUri) {
            $cleanUri = strtok($requestUri, '?');
            self::setRoute($method, $cleanUri, $prefix);
        }
    }

    /**
     * Parse URI → detect module → resolve CRUD endpoint.
     *
     * @return array|null
     */
    public static function getFromUri(Request $request = null): ?array
    {
        $request ??= request();

        $segments = $request->segments();
        $count    = count($segments);

        if ($count < 2) {
            return null;
        }

        $action = $segments[$count - 2];
        $scope  = $segments[$count - 1];

        $modules = ['ajax', 'admotum', 'sanctum'];
        $module  = collect($segments)
            ->intersect($modules)
            ->first();

        return [
            'requestPath' => '/' . implode('/', $segments),
            'module'      => $module,
            'endpoint'    => $action . '/' . $scope,
        ];
    }
}