<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedInProduction
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response|RedirectResponse
    {
        if (app()->environment(['local', 'development'])) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail())) {
            if ($request->expectsJson()) {
                abort(403, 'Your email address is not verified.');
            }

            return Redirect::guest(URL::route($redirectToRoute ?? 'verification.notice'));
        }

        return $next($request);
    }
}
