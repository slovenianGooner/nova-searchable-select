<?php

namespace Sloveniangooner\SearchableSelect;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\ServiceProvider;
use Sloveniangooner\SearchableSelect\Http\Middleware\Authorize;
use Route;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('searchable-select', __DIR__.'/../dist/js/field.js');
            Nova::style('searchable-select', __DIR__.'/../dist/css/field.css');
        });

        $this->app->booted(function () {
            $this->routes();
        });
    }

    /**
     * Register the field's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', Authorize::class])
                ->prefix('nova-vendor/searchable-select')
                ->group(__DIR__.'/../routes/api.php');
    }
}
