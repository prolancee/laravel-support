<?php

namespace PROLANCEE\Support\Console\Install;

use Illuminate\Console\Command;
use PROLANCEE\Support\Console\Concerns\SupportInstaller;
use Throwable;

class InstallNotifier extends Command
{
    use SupportInstaller;

    protected $signature = 'prolancee:support:notifier 
                            {--force : Overwrite existing notifier files}';

    protected $description = 'Install Prolancee notifier';

    public function handle(): int
    {
        $this->line('Installing Prolancee notifier...');

        $force = $this->option('force');
        $this->line('Force overwrite: ' . ($force ? 'YES' : 'NO'));

        try {
            $this->initPaths();

            $this->installNotifier($force);

            $this->info('✔ Notifier installed successfully.');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Notifier installation failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
