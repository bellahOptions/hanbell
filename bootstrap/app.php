<?php

use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\RedirectAdminsFromStorefront;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);

        // Resolve administrator-defined redirects only after routing has failed,
        // so a stale redirect can never shadow a live route.
        $middleware->prependToGroup('web', HandleRedirects::class);

        $middleware->alias([
            'customer' => RedirectAdminsFromStorefront::class,
            'role' => Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        /*
         * Gateway webhooks are server-to-server POSTs from Paystack,
         * Flutterwave and Stripe. They cannot carry a CSRF token, so they are
         * excluded here and authenticated by signature verification instead
         * (which is a stronger check than a CSRF token would be).
         *
         * The ad impression endpoint is excluded for the same reason:
         * navigator.sendBeacon cannot set a custom header. It is rate-limited
         * and de-duplicated instead.
         *
         * This must be a SINGLE call — a second call would replace this list.
         */
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'ads/impression',
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * A tampered or expired URL token must look exactly like a missing page.
         * Confirming "this link was altered" would tell an attacker their
         * forgery was understood, and which part to correct. Route binding
         * already returns null for a bad token, so the resulting 404 renders
         * the standard branded error page with no special casing needed.
         */

        // An expired session should return the shopper to where they were.
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired. Please try again.'], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'Your session expired for security reasons. Please try again.');
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please sign in to continue.'], 401);
            }

            return redirect()->guest(route('login'));
        });
    })->create();
