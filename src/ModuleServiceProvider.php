<?php

namespace MinionFactory\ModuleFactory;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public static $modules = null;
    private static $modulePath = '';

    public static function getModules()
    {
        if (self::$modules !== null) {
            return self::$modules;
        }
        self::$modules = [];

        self::$modulePath = app_path() . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR;

        if (is_dir(self::$modulePath)) {
            $folders = scandir(self::$modulePath);
            foreach ($folders as $folder) {
                if (is_dir(self::$modulePath . $folder) && $folder !== '.' && $folder !== '..' && ! preg_match('/-disabled/', $folder)) {
                    self::$modules[] = $folder;
                }
            }
        }

        return self::$modules;
    }

    public static function loadRouteResources($folderResource)
    {
        // For each of the registered modules, include their routes and Views
        $modules = self::getModules();

        foreach ($modules as $module) {
            if (file_exists(self::$modulePath . $module . DIRECTORY_SEPARATOR . $folderResource . DIRECTORY_SEPARATOR . 'api.php')) {
                $path = self::$modulePath . $module . DIRECTORY_SEPARATOR . $folderResource . DIRECTORY_SEPARATOR . 'api.php';
                Route::prefix('api')
                     ->middleware('api')
                     ->namespace("\App\Modules\\$module\Controllers")
                     ->group($path);
            }
            if (file_exists(self::$modulePath . $module . DIRECTORY_SEPARATOR . $folderResource . DIRECTORY_SEPARATOR . 'web.php')) {
                $path = self::$modulePath . $module . DIRECTORY_SEPARATOR . $folderResource . DIRECTORY_SEPARATOR . 'web.php';
                Route::middleware('web')
                     ->namespace("\App\Modules\\$module\Controllers")
                     ->group($path);
            }
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Boot Order.
        self::loadViews();
        self::loadResources('Config');
        self::loadResources('Policies');
        self::loadResources('Events' . DIRECTORY_SEPARATOR . 'Listeners');
        self::loadRouteResources('Routes');
        self::loadResources('Shortcodes');
    }

    private static function loadViews()
    {
        $modules = self::getModules();

        // TODO just read the folder instead for folders to include instead of using the config file. Unless the config file is updateable.

        foreach ($modules as $module) {
            // Load the views

            if (is_dir(self::$modulePath . $module . '/Views')) {
                View::addNamespace($module, self::$modulePath . $module . '/Views');
            }
        }
    }

    public static function loadResources($folderResource, $fileResource = null)
    {
        // For each of the registered modules, include their routes and Views
        $modules = self::getModules();

        // TODO just read the folder instead for folders to include instead of using the config file. Unless the config file is updatable.
//        $modulePath = base_path('Modules');

        foreach ($modules as $module) {
            if ($fileResource !== null) {
                if (file_exists(app_path(self::$modulePath . $module . '/' . $folderResource . '/' . $fileResource))) {
                    include app_path(self::$modulePath . $module . '/' . $folderResource . '/' . $fileResource);
                }
            } else {
                if ( ! is_dir(app_path(self::$modulePath . $module . '/' . $folderResource . '/'))) {
                    continue;
                }
                $moduleFiles = scandir(app_path(self::$modulePath . $module . '/' . $folderResource . '/'));
                foreach ($moduleFiles as $file) {
                    if (is_dir(app_path(self::$modulePath . $module . '/' . $folderResource . '/' . $file))) {
                        continue;
                    }
                    if ($file !== '.' && $file !== '..' && strpos($file, '-disabled') === false) {
                        include app_path(self::$modulePath . $module . '/' . $folderResource . '/' . $file);
                    }
                }
            }
        }
    }
}