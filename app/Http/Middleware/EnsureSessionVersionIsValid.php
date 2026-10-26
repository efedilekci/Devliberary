<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionVersionIsValid
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $storedVersion = $request->session()->get('session_version');

        if ($storedVersion === null) {
            $request->session()->put('session_version', $user->session_version);

            return $next($request);
        }

        if ((int) $storedVersion !== $user->session_version) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'status',
                __('Je sessie is verlopen. Log opnieuw in.'),
            );
        }

        return $next($request);
    }
}
