<?php

namespace Yomon\Foundation\Http;

use Exception;
use NoahBuscher\Macaw\Macaw;
use Throwable;
use Yomon\Contracts\Http\Kernel as KernelContract;
use Yomon\Foundation\Application;
use Yomon\Pipeline\Pipeline;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     *
     */
    protected $app;

    /**
     * The router instance.
     *
     * @var \Yomon\Routing\Router
     */
    protected $router;

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
        \Yomon\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Yomon\Foundation\Bootstrap\LoadConfiguration::class,
        \Yomon\Foundation\Bootstrap\LoadConfiguration::class,
        \Yomon\Foundation\Bootstrap\HandleExceptions::class,
        \Yomon\Foundation\Bootstrap\RegisterFacades::class,
        \Yomon\Foundation\Bootstrap\RegisterProviders::class,
        \Yomon\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [];

    /**
     * The priority-sorted list of middleware.
     *
     * Forces the listed middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Yomon\Session\Middleware\StartSession::class,
        \Yomon\View\Middleware\ShareErrorsFromSession::class,
        \Yomon\Auth\Middleware\Authenticate::class,
        \Yomon\Session\Middleware\AuthenticateSession::class,
        \Yomon\Routing\Middleware\SubstituteBindings::class,
        \Yomon\Auth\Middleware\Authorize::class,
    ];

    /**
     * Create a new HTTP kernel instance.
     *
     * @param  \Yomon\Contracts\Foundation\Application  $app
//     * @param  \Yomon\Routing\Router  $router
     * @return void
     */
    /**
     * Kernel constructor.
     * @param Application $app
     */
    public function __construct(Application $app/*, Router $router*/)
    {
        $this->app = $app;
        //todo router
//        $this->router = $router;
//
        /*$router->middlewarePriority = $this->middlewarePriority;

        foreach ($this->middlewareGroups as $key => $middleware) {
            $router->middlewareGroup($key, $middleware);
        }

        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->aliasMiddleware($key, $middleware);
        }*/
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param  \Yomon\Http\Request  $request
     * @return \Yomon\Http\Response
     */
    public function handle($request)
    {
        $response = $this->sendRequestThroughRouter($request);
        /*try {
            //$request->enableHttpMethodParameterOverride();

            $response = $this->sendRequestThroughRouter($request);
        } catch (Exception $e) {
         //echo "<pre>";print_r($e);die();
            //$this->reportException($e);

            //$response = $this->renderException($request, $e);
        } catch (Throwable $e) {
            //echo "<pre>";print_r($e);die();
            //$this->reportException($e = new FatalThrowableError($e));

            //$response = $this->renderException($request, $e);
        }*/

        /*$this->app['events']->dispatch(
            new Events\RequestHandled($request, $response)
        );*/

        return $response;
    }

    /**
     * Send the given request through the middleware / router.
     *
     * @param  \Yomon\Http\Request  $request
     * @return \Yomon\Http\Response
     */
    protected function sendRequestThroughRouter($request)
    {
        //$this->app->instance('request', $request);

        //Facade::clearResolvedInstance('request');

        $this->bootstrap();

        return (new Pipeline($this->app))
                    ->send($request)
                    ->through($this->middleware)
                    ->then($this->dispatchToRouter());
    }

    /**
     * Bootstrap the application for HTTP requests.
     *
     * @return void
     */
    public function bootstrap()
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    /**
     * Get the route dispatcher callback.
     *
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        return function ($request) {
            $this->app->instance('request', $request);

//            return $this->router->dispatch($request);
            $routeFiles = $this->app->configPath("routes.php");

//            require BASE_PATH.'/config/routes.php';
            require  $routeFiles;
            $request->response->return = Macaw::dispatch();;
            return $request->response;
        };
    }

    /**
     * Call the terminate method on any terminable middleware.
     *
     * @param  \Yomon\Http\Request  $request
     * @param  \Yomon\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        $this->terminateMiddleware($request, $response);

        $this->app->terminate();
    }

    /**
     * Call the terminate method on any terminable middleware.
     *
     * @param  \Yomon\Http\Request  $request
     * @param  \Yomon\Http\Response  $response
     * @return void
     */
    protected function terminateMiddleware($request, $response)
    {
        $middlewares = $this->app->shouldSkipMiddleware() ? [] : array_merge(
            $this->gatherRouteMiddleware($request),
            $this->middleware
        );

        foreach ($middlewares as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            list($name) = $this->parseMiddleware($middleware);

            $instance = $this->app->make($name);

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }

    /**
     * Gather the route middleware for the given request.
     *
     * @param  \Yomon\Http\Request  $request
     * @return array
     */
    protected function gatherRouteMiddleware($request)
    {
        if ($route = $request->route()) {
            return $this->router->gatherRouteMiddleware($route);
        }

        return [];
    }

    /**
     * Parse a middleware string to get the name and parameters.
     *
     * @param  string  $middleware
     * @return array
     */
    protected function parseMiddleware($middleware)
    {
        list($name, $parameters) = array_pad(explode(':', $middleware, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * Determine if the kernel has a given middleware.
     *
     * @param  string  $middleware
     * @return bool
     */
    public function hasMiddleware($middleware)
    {
        return in_array($middleware, $this->middleware);
    }

    /**
     * Add a new middleware to beginning of the stack if it does not already exist.
     *
     * @param  string  $middleware
     * @return $this
     */
    public function prependMiddleware($middleware)
    {
        if (array_search($middleware, $this->middleware) === false) {
            array_unshift($this->middleware, $middleware);
        }

        return $this;
    }

    /**
     * Add a new middleware to end of the stack if it does not already exist.
     *
     * @param  string  $middleware
     * @return $this
     */
    public function pushMiddleware($middleware)
    {
        if (array_search($middleware, $this->middleware) === false) {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function reportException(Exception $e)
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the exception to a response.
     *
     * @param  \Yomon\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderException($request, Exception $e)
    {
        return $this->app[ExceptionHandler::class]->render($request, $e);
    }

    /**
     * Get the Laravel application instance.
     *
     * @return \Yomon\Contracts\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}
