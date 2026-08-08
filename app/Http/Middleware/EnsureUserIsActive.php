<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        // Fail closed when an authenticated user's active state is not true.
        if ($user !== null && $user->is_active !== true) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'This account is currently inactive.',
                );
        }

        return $next($request);
    }
}
