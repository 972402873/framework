<?php

namespace Yomon\Foundation\Bootstrap;

use Yomon\Foundation\AliasLoader;
use Yomon\Foundation\Facade;
//use Illuminate\Foundation\PackageManifest;
use Yomon\Foundation\Application;

class RegisterFacades
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Yomon\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($app);

        AliasLoader::getInstance($app->make('config')->get('app.aliases', []))->register();
        /*AliasLoader::getInstance(array_merge(
            $app->make('config')->get('app.aliases', []),
        //$app->make(PackageManifest::class)->aliases()
        ))->register();*/
    }
}
