<?php

namespace PROLANCEE\Support\Console\Install;

use Illuminate\Console\Command;
use PROLANCEE\Support\Console\Concerns\SupportInstaller;
use Throwable;

class InstallSupport extends Command
{
    use SupportInstaller;

    protected $signature = 'prolancee:support:install 
                            {--force : Overwrite existing support files}';

    protected $description = 'Install all Prolancee support components';

    public function handle(): int
    {
        $this->line('Installing all Prolancee support components...');

        $force = $this->option('force');
        $this->line('Force overwrite: ' . ($force ? 'YES' : 'NO'));

        try {
            $this->initPaths();

            $this->line('→ Installing helpers');
            $this->installHelper($force);

            $this->line('→ Installing models');
            $this->installModels($force);

            $this->line('→ Installing render files');
            $this->installRender($force);

            $this->line('→ Installing notifier');
            $this->installNotifier($force);

            $this->info('✔ All support components installed successfully.');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Support installation failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
