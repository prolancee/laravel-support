<?php

namespace App\Models\Prolancee;

use App\Models\Prolancee\Classes\User;

final class Modeler
{
    /**
     * Registered PROLANCEE model classes.
     *
     * @return array<int, class-string>
     */
    final public static function modelClasses(): array
    {
        return [
            User::class,
        ];
    }
}
