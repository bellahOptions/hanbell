<?php

namespace App\Providers;

use App\Payments\PaymentGatewayManager;
use App\Services\Advertising\AdDestination;
use App\Services\Advertising\AdRanker;
use App\Services\Advertising\AdServer;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use App\Support\Tokens\UrlToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * The ad server MUST be a singleton per request.
         *
         * It accumulates which campaigns have already been placed so that one
         * advertiser cannot occupy every slot on a page (the fatigue
         * multiplier). A fresh instance per resolution would silently disable
         * that rule and let a single campaign fill the homepage.
         */
        $this->app->singleton(AdServer::class);

        $this->app->singleton(AdRanker::class, fn () => new AdRanker(
            (array) config('hanbell.ads.ranking', []),
        ));

        $this->app->singleton(AdDestination::class, fn () => new AdDestination(
            (array) config('hanbell.ads.allowed_redirect_hosts', []),
        ));

        $this->app->singleton(PaymentGatewayManager::class, fn () => new PaymentGatewayManager);

        $this->app->singleton(AuditLogger::class);

        /*
         * One Seo object per request, resolved from the container.
         *
         * A Livewire component builds its Seo metadata inside render() and the
         * surrounding layout renders the <head>. Passing the object between them
         * through view data is fragile — the layout is a separate view with its
         * own scope — so both sides resolve the same scoped instance instead.
         * A `scoped` binding (rather than `singleton`) means the object cannot
         * leak between requests in a long-lived worker.
         */
        $this->app->scoped(Seo::class, fn () => new Seo(request()->route()?->getName()));
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        /*
         * Nested relation loading is the single biggest source of N+1 queries in
         * a marketplace listing, so it is treated as an error outside
         * production. Tests are exempt: a lazy load inside a Livewire render is
         * ordinary behaviour there, and throwing would make the suite brittle
         * rather than faster.
         */
        Model::preventLazyLoading(! $this->app->isProduction() && ! $this->app->runningUnitTests());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Force https in production so tokens are never sent in the clear.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->configurePasswordRules();
        $this->registerBladeDirectives();
    }

    /**
     * One place to define what a strong password means, used by every form that
     * sets or resets one.
     */
    private function configurePasswordRules(): void
    {
        Password::defaults(function () {
            $rule = Password::min(10)->letters()->numbers()->mixedCase();

            // Uncompromised-password checking hits an external API, so it is
            // reserved for production to keep the test suite offline and fast.
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }

    private function registerBladeDirectives(): void
    {
        // @money(125000) / @money(125000, 'USD')
        Blade::directive('money', function (string $expression): string {
            return "<?php echo e(\App\Support\Money::format({$expression})); ?>";
        });

        // @compactmoney(125000000)
        Blade::directive('compactmoney', function (string $expression): string {
            return "<?php echo e(\App\Support\Money::compact({$expression})); ?>";
        });

        /**
         * @urltoken($model, 'products.show') — emit a route using the model's
         * signed token, so views never hand-assemble a tokenised URL.
         */
        Blade::directive('urltoken', function (string $expression): string {
            return "<?php echo e(route({$expression})); ?>";
        });

        // @locale / @direction for the active language.
        Blade::directive('direction', fn (): string => "<?php echo e(\App\Support\Locale::direction()); ?>");
    }
}
