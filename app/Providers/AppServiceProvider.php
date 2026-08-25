<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\SaleBanner;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        Paginator::useTailwind();

        View::composer('*', function ($view) {
            $cartCount = Auth::check()
                ? Cart::where('user_id', Auth::id())->count()
                : 0;

            $view->with('cartCount', $cartCount);
        });

        View::composer('maindesign', function ($view) {
            if (! Schema::hasTable('sale_banners')) {
                $view->with('activeSaleBanners', collect());
                $view->with('tickerBanners', collect());

                return;
            }

            $view->with('activeSaleBanners', SaleBanner::active()
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->take(3)
                ->get());

            $view->with('tickerBanners', SaleBanner::active()
                ->where('show_in_ticker', true)
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get());
        });
    }
}
