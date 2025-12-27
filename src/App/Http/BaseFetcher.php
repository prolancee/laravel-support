<?php

namespace PROLANCEE\Support\App\Http;

use PROLANCEE\Support\App\Services\BaseService;

abstract class BaseFetcher
{
    protected BaseService $service;

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
     * This method acts as the primary entry point for dynamic queries.
     * It accepts a callable that returns a configuration array, which is then
     * passed to the injected BaseService for execution.
     *
     * Dependency:
     * BaseService is injected via the BaseFetcher constructor.
     *
     * @param  callable  $callback  A closure returning the full fetcher config.
     * @return array|bool  Fetched data (array, collection, or paginated result).
     */
    protected function execute(callable $callback): array|bool
    {
        return $this->service->fetchBuilder($callback());
    }
}
