<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\SettingService;
use App\Observers\ActivityLogObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(\App\Models\Category::class, \App\Policies\CategoryPolicy::class);
        Gate::policy(\App\Models\Product::class, \App\Policies\ProductPolicy::class);
        Gate::policy(\App\Models\StockMovement::class, \App\Policies\StockMovementPolicy::class);
        Gate::policy(\App\Models\Customer::class, \App\Policies\CustomerPolicy::class);
        Gate::policy(\App\Models\Supplier::class, \App\Policies\SupplierPolicy::class);
        Gate::policy(\App\Models\Purchase::class, \App\Policies\PurchasePolicy::class);
        Gate::policy(\App\Models\Sale::class, \App\Policies\SalePolicy::class);
        Gate::policy(\App\Models\Expense::class, \App\Policies\ExpensePolicy::class);
        foreach ([
            \App\Models\Category::class, \App\Models\Product::class, \App\Models\Customer::class,
            \App\Models\Supplier::class, \App\Models\Sale::class, \App\Models\Purchase::class,
            \App\Models\StockMovement::class, \App\Models\Expense::class, \App\Models\Payment::class,
            \App\Models\User::class, \App\Models\Setting::class,
        ] as $model) {
            $model::observe(ActivityLogObserver::class);
        }
        View::composer('*', function ($view) {
            $settings = app(SettingService::class)->all();
            $view->with('businessSettings', $settings);
            $view->with('currency', $settings['currency'] ?? 'FCFA');
            $view->with('dateFormat', $settings['date_format'] ?? 'd/m/Y');
        });
        Paginator::useBootstrapFive();

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by(mb_strtolower($email).'|'.$request->ip());
        });
    }
}
