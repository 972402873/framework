<?php

namespace Yomon\Foundation\Bootstrap;

use Whoops\Handler\PrettyPageHandler;
use Yomon\Foundation\Application;
use Whoops\Run as Whoops;

class HandleExceptions
{
    /**
     * The application instance.
     *
     * @var \Yomon\Foundation\Application
     */
    protected $app;

    /**
     * Bootstrap the given application.
     *
     * @param  \Yomon\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $this->app = $app;

        if (! $app->environment('testing')) {
            //ini_set('display_errors', 'Off');
        }

        if (config('app.debug') && class_exists(Whoops::class)){
            $whoops = new Whoops;
            $whoops->pushHandler(new PrettyPageHandler());
            // $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler($monolog));
            $whoops->register();
        }


    }






}
