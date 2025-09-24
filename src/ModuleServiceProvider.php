<?php

namespace MinionFactory\ModuleFactory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public static ?array $modules = null;
    private static string $modulePath = '';

    public static function getModules(): ?array
    {
        if (self::$modules !== null) {
            return self::$modules;
        }
        self::$modules = [];

        self::$modulePath = app_path().DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR;

        if (is_dir(self::$modulePath)) {
            $folders = scandir(self::$modulePath);
            foreach ($folders as $folder) {
                if (is_dir(self::$modulePath.$folder) && $folder !== '.' && $folder !== '..' && ! str_contains($folder, '-disabled')) {
                    self::$modules[] = $folder;
                }
            }
        }

        return self::$modules;
    }

    public static function getRouteDirectives(): Collection
    {
        return collect([
            'api' => (object) ['middleware' => 'api', 'file' => 'api.php', 'prefix' => 'api'],
            'admin' => (object) ['middleware' => 'admin', 'file' => 'admin.php'],
            'web' => (object) ['middleware' => 'web', 'file' => 'web.php'],
            'console' => (object) ['file' => 'console.php'],
        ]);
    }

    public static function loadResources($folderResource, $fileResource = null): void
    {
        // For each of the registered modules, include their routes and Views
        $modules = self::getModules();

        // TODO just read the folder instead for folders to include instead of using the config file. Unless the config file is updatable.
//        $modulePath = base_path('Modules');

        foreach ($modules as $module) {
            if ($fileResource !== null) {
                $file = self::$modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR.$fileResource;
                if (file_exists($file)) {
                    include_once $file;
                }
            } else {
                $folder = self::$modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR;
                if ( ! is_dir($folder)) {
                    continue;
                }
                $moduleFiles = scandir($folder);
                foreach ($moduleFiles as $file) {
                    if (is_dir($folder.$file)) {
                        continue;
                    }
                    if ($file !== '.' && $file !== '..' && ! str_contains($file, '-disabled')) {
                        include_once $folder.$file;
                    }
                }
            }
        }
    }

    public static function loadRouteResources($folderResource): void
    {
        if (app()->routesAreCached()) {
            return;
        }
        // For each of the registered modules, include their routes and Views
        $modules = self::getModules();
        $routeDirectives = self::getRouteDirectives();

        foreach ($modules as $module) {
            foreach ($routeDirectives as $type => $directive) {
                $path = self::$modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR.$directive->file;
                if (file_exists($path)) {
                    if ($directive->prefix ?? false) {
                        Route::prefix($directive->prefix)
                             ->middleware($directive->middleware)
//                             ->namespace("\App\Modules\\$module\Controllers")
                             ->group($path);
                    } elseif ($type == 'console') {
                        require $path;
                    } else {
                        Route::middleware($directive->middleware)
//                             ->namespace("\App\Modules\\$module\Controllers")
                             ->group($path);
                    }
                }
            }
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Boot Order.
        self::loadViews();
        self::loadResources('Config');
        self::loadResources('Policies');
        self::loadResources('Events'.DIRECTORY_SEPARATOR.'Listeners');
        self::loadRouteResources('Routes');
        self::loadResources('Shortcodes');
    }

    private static function loadViews(): void
    {
        $modules = self::getModules();

        // TODO just read the folder instead for folders to include instead of using the config file. Unless the config file is updateable.

        foreach ($modules as $module) {
            // Load the views

            if (is_dir(self::$modulePath.$module.DIRECTORY_SEPARATOR.'Views')) {
                View::addNamespace($module, self::$modulePath.$module.DIRECTORY_SEPARATOR.'Views');
            }
        }
    }
}
