<?php

namespace PROLANCEE\Support\Classes\IO;

final class Render
{
    protected static array $DEFAULT_PREFIXES;
    protected string $prefix;
    protected string $class;

    protected function __construct(string $class)
    {
        self::ensureInitialized();
        $this->class = $class;
        $this->prefix = self::$DEFAULT_PREFIXES['blade'] ?? '';
    }

    protected static function ensureInitialized(): void
    {
        if (!isset(self::$DEFAULT_PREFIXES)) {
            self::$DEFAULT_PREFIXES = config('prolancee.crud.blade.partial_view_prefixes', [
                'blade' => 'partials.prolancee.blade.',
            ]);
        }
    }

    public static function blade(string $class): self
    {
        return new self($class);
    }

    /**
     * Perform a callable-based group operation using the configured prefix and class.
     *
     * @param callable $callback
     * @return mixed
     */
    public function group(callable $callback): mixed
    {
        if (is_callable($callback)) {
            return $callback($this->prefix, $this->class);
        }
        return null;
    }

    /**
     * Get the resolved view prefix for the current class context.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
