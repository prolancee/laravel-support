<?php

namespace App\Http\Prolancee;

use App\Http\Prolancee\Classes\Renderable;

/*
|---------------------------------------------------------------------------
| PROLANCEE Render Class Registration
|---------------------------------------------------------------------------
| This class defines all the renderable data builder classes used within
| PROLANCEE's dynamic Blade component system.
|
| Each listed class must implement standardized rendering output via
| PROLANCEE's Fetcher or similar data-pipeline-compatible systems.
*/
final class Render
{
    final public static function renderableClasses(): array
    {
        return [
            Renderable::class,
        ];
    }
}
