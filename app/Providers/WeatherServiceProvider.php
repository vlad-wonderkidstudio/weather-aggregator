<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WeatherService;
use App\Services\WeatherSources\WeatherSourceVisualcrossing;
use App\Services\WeatherSources\WeatherSourceWeatherapi;

class WeatherServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton(WeatherSourceVisualcrossing::class, function ($app) {
            return new WeatherSourceVisualcrossing();
        });

        $this->app->singleton(WeatherSourceWeatherapi::class, function ($app) {
            return new WeatherSourceWeatherapi();
        });

        $this->app->singleton(WeatherService::class, function ($app) {
            return new WeatherService(
                $app->make(WeatherSourceVisualcrossing::class),
                $app->make(WeatherSourceWeatherapi::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        //
    }
}