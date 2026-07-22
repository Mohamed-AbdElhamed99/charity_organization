<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\AuthenticatedHome;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
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

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,

        ]);

        // Single login entry point for guests (admin + account area).
        $middleware->redirectGuestsTo(fn () => route('account.login'));

        // Already-authenticated visitors hitting guest routes go home by permission.
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            return $user !== null
                ? AuthenticatedHome::url($user)
                : route('account.login');
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
