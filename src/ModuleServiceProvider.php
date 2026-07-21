<?php

namespace MinionFactory\ModuleFactory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use MinionFactory\ModuleFactory\Console\CacheModulesCommand;
use MinionFactory\ModuleFactory\Console\ClearModulesCommand;

class ModuleServiceProvider extends ServiceProvider
{
    public static ?array $modules = null;
    private static string $modulePath = '';

    /** false = not yet attempted, null = no cache file present, array = loaded manifest */
    private static array|false|null $manifest = false;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheModulesCommand::class,
                ClearModulesCommand::class,
            ]);
        }

        // Laravel 11+: hooks minion-modules:cache/:clear into `artisan optimize` /
        // `artisan optimize:clear` alongside config/route/view/event caching. Guarded
        // for Laravel 10 consumers, where ServiceProvider::optimizes() doesn't exist yet.
        if (method_exists($this, 'optimizes')) {
            $this->optimizes(
                optimize: 'minion-modules:cache',
                clear: 'minion-modules:clear',
                key: 'minion-modules',
            );
        }
    }

    public static function getModulePath(): string
    {
        if (self::$modulePath === '') {
            self::$modulePath = app_path().DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR;
        }

        return self::$modulePath;
    }

    public static function getManifestPath(): string
    {
        return function_exists('bootstrap_path')
            ? bootstrap_path('cache'.DIRECTORY_SEPARATOR.'minion_modules.php')
            : base_path('bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'minion_modules.php');
    }

    /**
     * Resource folders scanned per-module, besides Views (namespace-only) and Routes
     * (already handled separately via loadRouteResources()'s routesAreCached() check).
     */
    public static function getManifestResourceFolders(): array
    {
        return ['Config', 'Policies', 'Events'.DIRECTORY_SEPARATOR.'Listeners', 'Shortcodes'];
    }

    /**
     * Scans app/Modules once and returns the module list + resolved per-module resource
     * file paths. Written to disk by module:cache; boot() reads that file instead of
     * re-running scandir()/is_dir() on every request when it exists.
     */
    public static function buildManifest(): array
    {
        $modulePath = self::getModulePath();
        $modules = [];

        if (is_dir($modulePath)) {
            foreach (scandir($modulePath) as $folder) {
                if (is_dir($modulePath.$folder) && $folder !== '.' && $folder !== '..' && ! str_contains($folder, '-disabled')) {
                    $modules[] = $folder;
                }
            }
        }

        $viewModules = [];
        $resources = [];

        foreach ($modules as $module) {
            if (is_dir($modulePath.$module.DIRECTORY_SEPARATOR.'Views')) {
                $viewModules[] = $module;
            }

            foreach (self::getManifestResourceFolders() as $folderResource) {
                $folder = $modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR;
                if (! is_dir($folder)) {
                    continue;
                }
                foreach (scandir($folder) as $file) {
                    if (is_dir($folder.$file)) {
                        continue;
                    }
                    if ($file !== '.' && $file !== '..' && ! str_contains($file, '-disabled')) {
                        $resources[$folderResource][$module][] = $file;
                    }
                }
            }
        }

        return [
            'modules' => $modules,
            'viewModules' => $viewModules,
            'resources' => $resources,
        ];
    }

    /**
     * Loads the cached manifest once per request. Returns null when no cache file exists
     * (module:cache hasn't been run), in which case every caller falls back to its
     * original live scandir()/is_dir() behaviour.
     */
    private static function getManifest(): ?array
    {
        if (self::$manifest !== false) {
            return self::$manifest;
        }

        $path = self::getManifestPath();
        if (! file_exists($path)) {
            return self::$manifest = null;
        }

        return self::$manifest = require $path;
    }

    public static function getModules(): ?array
    {
        if (self::$modules !== null) {
            return self::$modules;
        }

        $manifest = self::getManifest();
        if ($manifest !== null) {
            return self::$modules = $manifest['modules'] ?? [];
        }

        self::$modules = [];
        $modulePath = self::getModulePath();

        if (is_dir($modulePath)) {
            $folders = scandir($modulePath);
            foreach ($folders as $folder) {
                if (is_dir($modulePath.$folder) && $folder !== '.' && $folder !== '..' && ! str_contains($folder, '-disabled')) {
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
        $modulePath = self::getModulePath();

        // TODO just read the folder instead for folders to include instead of using the config file. Unless the config file is updatable.
//        $modulePath = base_path('Modules');

        $manifest = $fileResource === null ? self::getManifest() : null;

        foreach ($modules as $module) {
            if ($fileResource !== null) {
                $file = $modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR.$fileResource;
                if (file_exists($file)) {
                    include_once $file;
                }

                continue;
            }

            if ($manifest !== null) {
                foreach ($manifest['resources'][$folderResource][$module] ?? [] as $file) {
                    include_once $modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR.$file;
                }

                continue;
            }

            $folder = $modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR;
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

    public static function loadRouteResources($folderResource): void
    {
        if (app()->routesAreCached()) {
            return;
        }
        // For each of the registered modules, include their routes and Views
        $modules = self::getModules();
        $modulePath = self::getModulePath();
        $routeDirectives = self::getRouteDirectives();

        foreach ($modules as $module) {
            foreach ($routeDirectives as $type => $directive) {
                $path = $modulePath.$module.DIRECTORY_SEPARATOR.$folderResource.DIRECTORY_SEPARATOR.$directive->file;
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
        $modulePath = self::getModulePath();
        $manifest = self::getManifest();

        // TODO just read the folder instead for folders to include instead of using the config file. Unless the config file is updateable.

        foreach ($modules as $module) {
            // Load the views

            if ($manifest !== null) {
                if (in_array($module, $manifest['viewModules'] ?? [], true)) {
                    View::addNamespace($module, $modulePath.$module.DIRECTORY_SEPARATOR.'Views');
                }

                continue;
            }

            if (is_dir($modulePath.$module.DIRECTORY_SEPARATOR.'Views')) {
                View::addNamespace($module, $modulePath.$module.DIRECTORY_SEPARATOR.'Views');
            }
        }
    }
}
