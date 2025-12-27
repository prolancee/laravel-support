<?php

namespace PROLANCEE\Support\App\Notifier;

interface NotifierInterface
{
    public function handle(array $payload): void;
}
