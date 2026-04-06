<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Review;
use App\Observers\ReviewObserver;
use App\Models\Order;
use App\Observers\OrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
{
    $this->app->bind(
        LoginResponse::class,
        \App\Http\Responses\LoginResponse::class
    );
}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Fix for older MySQL versions/MariaDB string lengths
        Schema::defaultStringLength(191);

        /**
         * 🚀 AI AUTOMATION
         * This line tells Laravel: "Every time a Review is created, 
         * look at the ReviewObserver to run the AI logic."
         */
        Review::observe(ReviewObserver::class);

        Order::observe(OrderObserver::class);
    }
}