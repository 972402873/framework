<?php

namespace Yomon\Facades;

use Yomon\Foundation\Facade;

/**
 *
 * @see \Yomon\Routing\Router
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}
