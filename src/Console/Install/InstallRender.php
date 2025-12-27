<?php

namespace PROLANCEE\Support\Console\Install;

use Illuminate\Console\Command;
use PROLANCEE\Support\Console\Concerns\SupportInstaller;
use Throwable;

class InstallRender extends Command
{
    use SupportInstaller;

    protected $signature = 'prolancee:support:render 
                            {--force : Overwrite existing render files}';

    protected $description = 'Install Prolancee render files';

    public function handle(): int
    {
        $this->line('Installing Prolancee render files...');
        
        $force = $this->option('force');
        $this->line('Force overwrite: ' . ($force ? 'YES' : 'NO'));

        try {
            $this->initPaths();

            $this->installRender($force);
            $this->ensureViewFolderExists();

            $this->info('✔ Render files installed successfully.');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Render installation failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
