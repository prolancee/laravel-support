<?php

namespace PROLANCEE\Support\Console\Install;

use Illuminate\Console\Command;
use PROLANCEE\Support\Console\Concerns\SupportInstaller;
use Throwable;

class InstallModels extends Command
{
    use SupportInstaller;

    protected $signature = 'prolancee:support:models 
                            {--force : Overwrite existing model files}';

    protected $description = 'Install Prolancee models';

    public function handle(): int
    {
        $this->line('Installing Prolancee models...');

        $force = $this->option('force');
        $this->line('Force overwrite: ' . ($force ? 'YES' : 'NO'));

        try {
            $this->initPaths();

            $this->installModels($force);

            $this->info('✔ Models installed successfully.');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Model installation failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
