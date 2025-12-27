<?php

namespace PROLANCEE\Support\App\Notifier;

use PROLANCEE\Support\App\Notifier\NotifierInterface;

abstract class AbstractNotifier implements NotifierInterface
{
    /**
     * Handle the notifier payload.
     */
    abstract public function handle(array $payload): void;
}
