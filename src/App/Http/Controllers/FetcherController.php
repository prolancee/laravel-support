<?php

namespace PROLANCEE\Support\App\Http\Controllers;

use App\Http\Controllers\Controller;
use PROLANCEE\Support\App\Services\BaseService;

class FetcherController extends Controller 
{
    protected $service;

    /**
     * Constructor to inject the BaseService dependency.
     *
     * @param BaseService $service
     */
    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }

    /**
     * Fetch data via configuration using BaseService.
     *
     * This static method acts as the primary entry point for dynamic queries.
     * It accepts a callable that returns a configuration array, which is then
     * passed to the BaseService for execution.
     *
     * Dependency:
     * Automatically resolves `BaseService` using Laravel’s service container.
     *
     * @param  callable  $callback  A closure returning the full fetcher config.
     * @return mixed     Fetched data (array, collection, or paginated result).
     */
    public static function fetching(callable $callback)
    {
        $config = $callback();
        $service = app(BaseService::class);

        return $service->fetchBuilder($config);
    }
} 