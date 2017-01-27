<?php
namespace ZanySoft\Cpanel;

use Illuminate\Support\Facades\Facade;

class CpanelFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'cpanel';
    }

    /*public function register()
    {
        $this->app['CpanelApi'] = $this->app->share(function($app)
        {
            return new CpanelApi;
        });

        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('CpanelApi', 'ZanySoft\Cpanel\CpanelApiFacade');
        });
    }*/
}