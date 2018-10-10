<?php

namespace Awescrm\CdnGCS;

use Illuminate\Support\ServiceProvider;

class CdnGCSServiceProvider extends ServiceProvider
{

    private $config = 'cdn-gcs';

    /**
     * Bootstrap the additional application helpers.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the additional application helpers.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cdngcs', function($app)
        {
            //$config = $app->config->get($this->config);
            $config = (include 'App/config.php');
            $storage = app(\Storage::class);
            $disk = $storage::disk('gcs');
            $locally = 'en';
            return new CdnGCS($app->cache->driver(), $config, $disk, $locally);
        });
    }
}
