<?php

namespace PROLANCEE\Support\Providers;

use Illuminate\Support\ServiceProvider;
use PROLANCEE\Support\App\Repositories\BaseRepositoryInterface;
use PROLANCEE\Support\App\Repositories\Eloquent\BaseRepository;

use PROLANCEE\Support\Console\Install\{
    InstallSupport,
    InstallHelper,
    InstallModels,
    InstallRender,
    InstallNotifier
};

use PROLANCEE\Support\Console\Make\{
    MakeModel,
    MakeRender,
};

use PROLANCEE\Support\Console\Init\{
    DBEncrypt,
};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }
        $this->publishConfig();

        $this->commands([
            InstallSupport::class,
            InstallHelper::class,
            InstallModels::class,
            InstallRender::class,
            InstallNotifier::class,
            MakeModel::class,
            MakeRender::class,
            DBEncrypt::class,
        ]);
    }

    /**
     * Publishes config files.
     */
    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => $this->configPath('prolancee/support.php'),
        ], 'prolancee:support:config');
    }

    /**
     * Resolve the target configuration file path for publishing.
     */
    private function configPath(string $file = ''): string
    {
        return base_path('config' . ($file ? '/' . $file : ''));
    }
}
