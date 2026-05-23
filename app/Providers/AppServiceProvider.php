<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Cart;
use App\Models\SaleBanner;


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
        Paginator::useBootstrapFive();

        View::composer('*', function ($view) {
            $cartCount = Auth::check()
                ? Cart::where('user_id', Auth::id())->count()
                : 0;

            $view->with('cartCount', $cartCount);
        });

        View::composer('maindesign', function ($view) {
            if (! Schema::hasTable('sale_banners')) {
                $view->with('activeSaleBanners', collect());

                return;
            }

            $view->with('activeSaleBanners', SaleBanner::active()
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->take(3)
                ->get());
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verify Your Email - The Daily Cut')
                ->greeting('Welcome to The Daily Cut!')
                ->line('You’re one step away from ordering fresh, premium meat.')
                ->action('Verify Email', $url)
                ->line('If you didn’t create an account, no action is required.')
                ->salutation('— The Daily Cut Team');
        });
    }
}
