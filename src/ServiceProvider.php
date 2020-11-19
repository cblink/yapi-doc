<?php

namespace Cblink\YApiDoc;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

/**
 * Class ServiceProvider
 * @package Cblink\YApiDoc
 */
class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        if (in_array($this->app->environment(), ['local', 'testing'])) {

            $this->commands([Commands\UploadDocToYApi::class]);

            $this->publishes([__DIR__ . '/../config/' => config_path()]);
        }
    }
}
