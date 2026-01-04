<?php

namespace PROLANCEE\Support\Console\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRender extends Command
{
    protected $signature = 'prolancee:make:render {name} {--force}';

    protected $description = 'Create a render class in Prolancee Classes and register it in Render.php';

    public function handle(): int
    {
        $nameInput = $this->argument('name');
        $force = $this->option('force');

        $parts = array_map(
            fn ($part) => Str::studly($part),
            explode('/', $nameInput)
        );

        $className = array_pop($parts);
        $subNamespace = count($parts) ? '\\' . implode('\\', $parts) : '';
        $subPath = count($parts) ? '/' . implode('/', $parts) : '';

        // Single source of truth (UNCHANGED)
        $baseNamespace = 'App\\Http\\Prolancee\\Classes';
        $basePath = app_path('Http/Prolancee/Classes');

        $directory = $basePath . $subPath;
        $filePath = "{$directory}/{$className}.php";

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (File::exists($filePath) && ! $force) {
            $this->warn("Render class '{$className}' already exists at:");
            $this->line($filePath);
            $this->warn('Use --force to overwrite.');
            return self::SUCCESS;
        }

        // =======================
        // RENDER CLASS STUB (UNCHANGED)
        // =======================
        $stub = <<<'PHP'
<?php

namespace {{namespace}};

use PROLANCEE\Support\Classes\IO\Fetcher;
use Illuminate\Support\Facades\DB;

class {{class}}
{
    // Add your rendering logic here
}
PHP;

        $stub = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$baseNamespace . $subNamespace, $className],
            $stub
        );

        File::put($filePath, $stub);

        // Register in Render.php (UNCHANGED LOGIC)
        $this->updateRenderFile($parts, $className, $baseNamespace);

        $this->info("✔ Render class '{$className}' created successfully at:");
        $this->line($filePath);

        return self::SUCCESS;
    }

    private function updateRenderFile(array $parts, string $className, string $baseNamespace): void
    {
        $renderPath = app_path('Http/Prolancee/Render.php');
        $namespacePath = count($parts) ? '\\' . implode('\\', $parts) : '';
        $fqcn = "{$baseNamespace}{$namespacePath}\\{$className}";

        // Create Render.php if missing (UNCHANGED)
        if (! File::exists($renderPath)) {
            $content = <<<PHP
<?php

namespace App\Http\Prolancee;

use {$fqcn};

final class Render
{
    public static function renderableClasses(): array
    {
        return [
            {$className}::class,
        ];
    }
}
PHP;
            File::put($renderPath, $content);
            return;
        }

        $renderContent = File::get($renderPath);

        // Add use statement
        $importLine = "use {$fqcn};";
        if (! str_contains($renderContent, $importLine)) {
            $renderContent = preg_replace(
                '/(namespace\s+App\\\\Http\\\\Prolancee;\s*)/m',
                "$1\n{$importLine}\n",
                $renderContent,
                1
            );
        }

        // Register class
        if (! str_contains($renderContent, "{$className}::class")) {
            $renderContent = preg_replace(
                '/return\s*\[\s*(.*?)\s*\];/s',
                "return [\n            $1\n            {$className}::class,\n        ];",
                $renderContent
            );
        }

        // Cleanup
        $renderContent = preg_replace("/\n{3,}/", "\n\n", $renderContent);
        $renderContent = preg_replace("/,\n\s*\n/", ",\n", $renderContent);

        File::put($renderPath, $renderContent);
    }
}
