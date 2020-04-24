<?php

namespace Garbetjie\Laravel\JsonApi;

use Illuminate\Support\Facades\DB;
use function config_path;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'garbetjie-jsonapi');

        $this->publishes(
            [__DIR__ . '/../config.php' => config_path('garbetjie-jsonapi')],
            ['config']
        );
    }
}
