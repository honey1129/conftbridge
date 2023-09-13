<?php

namespace App\Providers;

use Encore\Admin\Config\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists(Config::class)) {
            Config::load();
        }
        ini_set("error_reporting","E_ALL & ~E_NOTICE");

        Queue::after(function (JobProcessed $event) {
            // $event->connectionName
            \Log::info('getQueue'.$event->job->getQueue());
            // $event->job->payload()
        });
    }
}
