<?php

namespace App\Providers;

use App\Models\Audiobook;
use App\Policies\AudiobookPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Audiobook::class, AudiobookPolicy::class);
    }
}
