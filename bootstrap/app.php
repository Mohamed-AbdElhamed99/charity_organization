<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/site.php'));
            Route::middleware('web')
                ->group(base_path('routes/account.php'));
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,

        ]);

        // The `auth` middleware protects both admin routes and the donor
        // `/account/*` area; send unauthenticated visitors to the matching
        // login page instead of always defaulting to the admin login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('account/*', 'donations/subscriptions/*/portal')
            ? route('account.login')
            : route('login'));

        // The `guest` middleware (used by both admin's Fortify routes and
        // the donor `/account/*` auth routes) redirects an already
        // authenticated visitor away; send staff to the admin panel and
        // everyone else (donors) to their own account area.
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            return $user !== null && $user->hasAnyRole(['super_admin', 'staff', 'field_worker'])
                ? route('admin.dashboard')
                : route('account.donations.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if ($request->expectsJson()) {
                return $response;
            }

            if ($response->getStatusCode() === 403) {
                return Inertia::render('admin/errors/forbidden')
                    ->toResponse($request)
                    ->setStatusCode(403);
            }

            if ($response->getStatusCode() === 404) {
                return Inertia::render('errors/not-found')
                    ->toResponse($request)
                    ->setStatusCode(404);
            }

            return $response;
        });
    })->create();
