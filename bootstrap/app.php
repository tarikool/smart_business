<?php

use App\Exceptions\ExceptionHandler;
use App\Http\Middleware\CheckOwner;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '/api/v2',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.owner' => CheckOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (\Throwable $e, Request $request) {
            $isJsonResponse = $request->expectsJson() || $request->ajax() || $request->isJson() || $request->is('api/*');
            if ($isJsonResponse) {
                return (new ExceptionHandler)->render($e, $request);
            }
        });

        $exceptions->reportable(function (Throwable $e) {
            logger()->error($e->getMessage(), [
                'user_id' => auth()->id(),
                'uri' => \request()->getRequestUri(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => \request()->all(),
            ]);

            // returning false tells laravel "don't do the default report", will omit default log
            return false;
        });
    })
    ->create();
