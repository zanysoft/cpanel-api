<?php

namespace ZanySoft\Cpanel;

use Illuminate\Support\ServiceProvider;

class CpanelServiceProvider extends ServiceProvider
{


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cpanel.php', 'cpanel'
        );

        $this->registerCpanelService();

        if ($this->app->runningInConsole()) {
            $this->registerResources();
        }
    }

    /**
     * Register currency provider.
     *
     * @return void
     */
    public function registerCpanelService()
    {
        $this->app->singleton('cpanel', function ($app) {
            $config = $app['config']->get('cpanel');

            $host = $config['host'] ?? null;
            $username = $config['username'] ?? null;
            $password = $config['password'] ?? null;

            return new Cpanel($host, $username, $password);
        });
    }

    /**
     * Register currency resources.
     *
     * @return void
     */
    public function registerResources()
    {
        if ($this->isLumen() === false) {
            $this->publishes([
                __DIR__ . '/../config/cpanel.php' => config_path('cpanel.php'),
            ], 'config');
        }
    }

    /**
     * Check if package is running under Lumen app
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
}

?>
