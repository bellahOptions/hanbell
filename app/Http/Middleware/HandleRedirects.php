<?php

namespace App\Http\Middleware;

use App\Models\Redirect as RedirectModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies administrator-defined redirects before the router runs.
 *
 * Only reached when no route matched, so a redirect can never shadow a real
 * page — it exists to keep old links alive after a slug change.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        // Never redirect admin, API or webhook traffic.
        if ($request->is('admin/*', 'api/*', 'webhooks/*', 'up')) {
            return $next($request);
        }

        $redirect = RedirectModel::matchFor($request->path());

        if ($redirect) {
            $redirect->recordHit();

            return redirect()->to(
                str_starts_with($redirect->to_path, 'http')
                    ? $redirect->to_path
                    : '/'.ltrim($redirect->to_path, '/'),
                $redirect->status_code,
            );
        }

        return $next($request);
    }
}
