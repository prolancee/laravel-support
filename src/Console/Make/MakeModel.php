<?php

namespace PROLANCEE\Support\Console\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModel extends Command
{
    protected $signature = 'prolancee:make:model {name} {--force}';

    protected $description = 'Create a model with fillable & mandatory template inside Prolancee Classes and register it in Modeler.php';

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

        $directory = app_path('Models/Prolancee/Classes' . $subPath);
        $path = "{$directory}/{$className}.php";

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (File::exists($path) && ! $force) {
            $this->warn("Model {$className} already exists at {$path}");
            $this->warn('Use --force to overwrite.');
            return self::SUCCESS;
        }

        // =======================
        // MODEL STUB (UNCHANGED)
        // =======================
        $stub = <<<'PHP'
<?php

namespace {{namespace}};

use PROLANCEE\Support\App\Models\BaseSecureModel;

class {{class}} extends BaseSecureModel
{
    /** @var string Database table name */
    protected $table = '';

    /** @var array<int, string> */
    protected $fillable = [
        // 'field',
    ];

    /**
     * Mandatory fields per endpoint.
     *
     * @var array<string, mixed>
     */
    protected array $mandatory = [
        '{intermediate}/endpoint-url' => [
            'required' => [
                // 'field' => 'Error message',
            ],
        ],
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'field',
    ];
}
PHP;

        $stub = str_replace(
            ['{{namespace}}', '{{class}}'],
            ["App\\Models\\Prolancee\\Classes{$subNamespace}", $className],
            $stub
        );

        File::put($path, $stub);

        // Update Modeler.php (UNCHANGED LOGIC)
        $this->updateModeler($parts, $className);

        $this->info("✔ Model {$className} created successfully at:");
        $this->line($path);

        return self::SUCCESS;
    }

    private function updateModeler(array $parts, string $className): void
    {
        $modelerPath = app_path('Models/Prolancee/Modeler.php');
        $namespacePath = count($parts) ? '\\' . implode('\\', $parts) : '';
        $fqcn = "App\\Models\\Prolancee\\Classes{$namespacePath}\\{$className}";

        if (! File::exists($modelerPath)) {
            $content = <<<PHP
<?php

namespace App\Models\Prolancee;

use {$fqcn};

final class Modeler
{
    /**
     * Registered PROLANCEE model classes.
     *
     * @return array<int, class-string>
     */
    public static function modelClasses(): array
    {
        return [
            {$className}::class,
        ];
    }
}
PHP;
            File::put($modelerPath, $content);
            return;
        }

        $modelerContent = File::get($modelerPath);

        $importLine = "use {$fqcn};";
        if (! str_contains($modelerContent, $importLine)) {
            $modelerContent = preg_replace(
                '/(namespace\s+App\\\\Models\\\\Prolancee;\s*)/m',
                "$1\n{$importLine}\n",
                $modelerContent,
                1
            );
        }

        if (! str_contains($modelerContent, "{$className}::class")) {
            $modelerContent = preg_replace(
                '/return\s+\[\s*(.*?)\s*\];/s',
                "return [\n            $1\n            {$className}::class,\n        ];",
                $modelerContent
            );
        }

        $modelerContent = preg_replace("/\n{3,}/", "\n\n", $modelerContent);
        $modelerContent = preg_replace("/,\n\s*\n/", ",\n", $modelerContent);

        File::put($modelerPath, $modelerContent);
    }
}
