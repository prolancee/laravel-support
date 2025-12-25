<?php

namespace PROLANCEE\Support\Console\Concerns;

use Illuminate\Support\Facades\File;

trait SupportInstaller
{
    protected array $paths = [];
    protected string $appHelperFolderPath;

    protected function initPaths(): void
    {
        $this->appHelperFolderPath = $this->detectHelperFolder();

        $this->paths = [
            'helper'   => $this->appHelperFolderPath,
            'models'   => app_path('Models/Prolancee'),
            'http'     => app_path('Http/Prolancee'),
            'notifier' => app_path('Notifier'),
        ];
    }

    /* =========================
     | INSTALLERS (FORCE AWARE)
     ========================= */

    protected function installHelper(bool $force = false): void
    {
        $this->copyFile(
            __DIR__.'/../../App/Helpers/Helper.php',
            $this->paths['helper'].'/Prolancee.php',
            $force
        );
    }

    protected function installModels(bool $force = false): void
    {
        $this->copyDir(
            __DIR__.'/../../App/Models/Classes',
            $this->paths['models'].'/Classes',
            $force
        );

        $this->copyFile(
            __DIR__.'/../../App/Models/Modeler.php',
            $this->paths['models'].'/Modeler.php',
            $force
        );
    }

    protected function installRender(bool $force = false): void
    {
        $this->copyDir(
            __DIR__.'/../../App/Http/Render/Classes',
            $this->paths['http'].'/Classes',
            $force
        );

        $this->copyFile(
            __DIR__.'/../../App/Http/Render/Render.php',
            $this->paths['http'].'/Render.php',
            $force
        );
    }

    protected function installNotifier(bool $force = false): void
    {
        $this->copyFile(
            __DIR__.'/../../App/Notifier/Notify.php',
            $this->paths['notifier'].'/Prolancee.php',
            $force
        );
    }

    /* =========================
     | FILE SYSTEM HELPERS
     ========================= */

    protected function copyFile(string $from, string $to, bool $force = false): void
    {
        if (File::exists($to) && ! $force) {
            return;
        }

        File::ensureDirectoryExists(dirname($to));
        File::copy($from, $to);
    }

    protected function copyDir(string $from, string $to, bool $force = false): void
    {
        if (File::exists($to)) {
            if (! $force) {
                return;
            }

            File::deleteDirectory($to);
        }

        File::ensureDirectoryExists($to);
        File::copyDirectory($from, $to);
    }

    /* =========================
     | HELPER PATH DETECTION
     ========================= */

    protected function detectHelperFolder(): string
    {
        foreach (['Helpers', 'Helper', 'helpers', 'helper'] as $folder) {
            if (File::isDirectory(app_path($folder))) {
                return app_path($folder);
            }
        }

        return tap(app_path('Helpers'), fn ($path) =>
            File::makeDirectory($path, 0755, true)
        );
    }
}
