<?php

namespace PROLANCEE\Support\Console\Install;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PROLANCEE\Support\Console\Concerns\SupportInstaller;
use Throwable;

class InstallHelper extends Command
{
    use SupportInstaller;

    protected $signature = 'prolancee:support:helper 
                            {--force : Overwrite existing helper files}';

    protected $description = 'Install and load Prolancee helper files';

    public function handle(): int
    {
        $this->line('Installing Prolancee helper files...');

        $force = $this->option('force');
        $this->line('Force overwrite: ' . ($force ? 'YES' : 'NO'));

        try {
            $this->initPaths();

            $this->installHelper($force);

            $this->loadHelperFiles();

            $this->info('✔ Helper installed and loaded successfully.');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Helper installation failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Load helper PHP files at runtime.
     */
    protected function loadHelperFiles(): void
    {
        $helperPath = $this->paths['helper'] ?? null;

        if (! $helperPath || ! is_dir($helperPath)) {
            $this->warn('Helper directory not found. Skipping load.');
            return;
        }

        foreach (glob($helperPath . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            if (File::isFile($file)) {
                require_once $file;
            }
        }

        $this->line('Helper files loaded.');
    }
}
