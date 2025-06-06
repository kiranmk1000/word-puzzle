<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\WordSubmissionServiceInterface;
use App\Services\Interfaces\PuzzleServiceInterface;
use App\Services\WordSubmissionService;
use App\Services\PuzzleService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PuzzleServiceInterface::class, PuzzleService::class);
        $this->app->bind(WordSubmissionServiceInterface::class, WordSubmissionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
