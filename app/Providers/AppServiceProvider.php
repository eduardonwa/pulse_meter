<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend(
            HtmlSanitizerConfig::class,
            fn (HtmlSanitizerConfig $config): HtmlSanitizerConfig =>
                $config->allowElement('iframe', [
                    'src',
                    'title',
                    'loading',
                    'allow',
                    'allowfullscreen',
                ]),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        RateLimiter::for(
            'product-analytics',
            function (Request $request) {
                return Limit::perMinute(120)
                    ->by($request->ip());
            }
        );

        RateLimiter::for(
            'article-search',
            fn (Request $request) => Limit::perMinute(20)
                ->by($request->ip()),
        );

        RateLimiter::for(
            'content-questions',
            fn (Request $request) => Limit::perHour(5)
                ->by(
                    $request->ip().'|'.(string) $request->string('email'),
                ),
        );

        Gate::define(
            'use-pro',
            fn (User $user): bool => $user->hasProAccess(),
        );

        Gate::define(
            'use-paid-pro',
            fn (User $user): bool => $user->hasPaidProAccess(),
        );
    }
}
