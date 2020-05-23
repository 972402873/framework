<?php

namespace Yomon\Foundation\Bootstrap;

use Exception;
use SplFileInfo;
use Yomon\Config\Repository;
use Symfony\Component\Finder\Finder;
use Yomon\Foundation\Application;

class LoadConfiguration
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Yomon\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $items = [];

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise
        // we will need to spin through every configuration file and load them all.
        if (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        // Next we will spin through all of the configuration files in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this app.
        $app->instance('config', $config = new Repository($items));

        if (! isset($loadedFromCache)) {
            $this->loadConfigurationFiles($app, $config);
        }

        // Finally, we will set the application's environment based on the configuration
        // values that were loaded. We will pass a callback which will be used to get
        // the environment in a web context where an "--env" switch is not present.

        $app->detectEnvironment(function () use ($config) {
            return $config->get('app.env', 'production');
        });

        date_default_timezone_set($config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * Load the configuration items from all of the files.
     *
     * @param  \Yomon\Foundation\Application  $app
     * @param  \Yomon\Config\Repository  $repository
     * @return void
     * @throws \Exception
     */
    protected function loadConfigurationFiles(Application $app, Repository $repository)
    {
        $files = $this->getConfigurationFiles($app);

        /*if (! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }*/

        foreach ($files as $key => $path) {
            $repository->set($key, require $path);
        }
    }

    /**
     * Get all of the configuration files for the application.
     *
     * @param  \Yomon\Foundation\Application  $app
     * @return array
     */
    protected function getConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());
        $configExt  = $app->getConfigExt();

        $files = $this->getDirAllFiles($configPath, $configExt);

        /*foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath);

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }*/

        ksort($files, SORT_NATURAL);

        return $files;
    }
    protected function getDirAllFiles($path, $ext){
        if (!is_dir($path)) return false;
        $scandir = scandir($path);
        $files = [];
        foreach ($scandir as $file) {
            if (is_dir($path.DIRECTORY_SEPARATOR.$file) && $file != '.' && $file != '..'){
                $children = $this->getDirAllFiles($path.DIRECTORY_SEPARATOR.$file,$ext);
                foreach ($children as $k => $child){
                    $files[$file.'.'.$k] = $child;
                }
            }else{
                if ('.' . pathinfo($file, PATHINFO_EXTENSION) === $ext) {
                    $basename = basename($file, '.php');

                    $files[$basename] = $path.DIRECTORY_SEPARATOR.$file;
                }
            }

        }
        return $files;
    }

    /**
     * Get the configuration file nesting path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $configPath
     * @return string
     */
    protected function getNestedDirectory(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }
}
