<?php

namespace App\Providers;

use App\Models\Media;
use App\Models\User;
use App\Policies\MediaPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policies([
            User::class => UserPolicy::class,
            Media::class => MediaPolicy::class,
        ]);  
        
    }
}
