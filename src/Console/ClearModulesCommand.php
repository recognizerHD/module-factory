<?php

namespace MinionFactory\ModuleFactory\Console;

use Illuminate\Console\Command;
use MinionFactory\ModuleFactory\ModuleServiceProvider;

class ClearModulesCommand extends Command
{
    protected $signature = 'minion-modules:clear';

    protected $description = 'Clear the cached app/Modules resource manifest';

    public function handle(): int
    {
        $path = ModuleServiceProvider::getManifestPath();

        if (file_exists($path)) {
            unlink($path);
        }

        $this->info('Module manifest cache cleared.');

        return self::SUCCESS;
    }
}
