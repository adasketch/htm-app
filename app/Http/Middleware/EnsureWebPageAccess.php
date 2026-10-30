<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebPageAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$privileges): Response
    {
        $plainTextToken = $request->cookie('hotel_task_page_token');
        $accessToken = is_string($plainTextToken)
            ? PersonalAccessToken::findToken($plainTextToken)
            : null;

        if (! $accessToken || ($accessToken->expires_at?->isPast() ?? false)) {
            return redirect('/login')->withCookie(cookie()->forget('hotel_task_page_token'));
        }

        $user = $accessToken->tokenable;
        if (! $user instanceof User || $user->approval_status !== 'approved') {
            return redirect('/login')->withCookie(cookie()->forget('hotel_task_page_token'));
        }

        if ($privileges !== [] && ! collect($privileges)->contains(fn (string $privilege): bool => $user->hasPrivilege($privilege))) {
            abort(403);
        }

        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }
}
