<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\SaleBanner;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Cache key for the active sale banners used on the storefront.
     */
    public const SALE_BANNERS_CACHE_KEY = 'sale_banners.active';

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

        View::composer('maindesign', function ($view) {
            $view->with('activeSaleBanners', $this->cachedActiveBanners());
            $view->with('tickerBanners', $this->cachedTickerBanners());
        });

        // The cart badge is rendered by the shared layout on every page. Scoping
        // this to the layout (rather than a '*' composer) keeps it to a single
        // lightweight query per request instead of running on every nested view.
        View::composer('maindesign', function ($view) {
            $view->with('cartCount', Auth::check()
                ? Cart::where('user_id', Auth::id())->count()
                : 0);
        });
    }

    protected function activeBannersQuery()
    {
        return SaleBanner::active()
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }

    protected function cachedActiveBanners()
    {
        return Cache::remember(self::SALE_BANNERS_CACHE_KEY, now()->addMinutes(15), function () {
            return $this->activeBannersQuery()->take(3)->get();
        });
    }

    protected function cachedTickerBanners()
    {
        return Cache::remember(self::SALE_BANNERS_CACHE_KEY.'.ticker', now()->addMinutes(15), function () {
            return $this->activeBannersQuery()
                ->where('show_in_ticker', true)
                ->get();
        });
    }
}
