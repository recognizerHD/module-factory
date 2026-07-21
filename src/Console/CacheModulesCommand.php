<?php

namespace MinionFactory\ModuleFactory\Console;

use Illuminate\Console\Command;
use MinionFactory\ModuleFactory\ModuleServiceProvider;

class CacheModulesCommand extends Command
{
    protected $signature = 'minion-modules:cache';

    protected $description = 'Cache the app/Modules resource manifest (Views/Config/Policies/Events-Listeners/Shortcodes) so boot() skips scandir() on every request';

    public function handle(): int
    {
        $manifest = ModuleServiceProvider::buildManifest();
        $path = ModuleServiceProvider::getManifestPath();

        if ( ! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, '<?php return '.var_export($manifest, true).';'.PHP_EOL);

        $this->info('Module manifest cached successfully ('.count($manifest['modules']).' modules).');

        return self::SUCCESS;
    }
}
