<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $modelKey): Response
    {
        $model = $request->route()->parameter($modelKey);

        abort_if(! $model || auth()->id() != $model->user_id, 403, 'Unauthorized action.');

        return $next($request);
    }
}
