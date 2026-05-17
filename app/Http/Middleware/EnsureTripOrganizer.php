<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the user has organizer role for the current trip.
 * Must be used after EnsureTripMember middleware.
 */
class EnsureTripOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->get('trip_role') !== 'organizer') {
            abort(403, 'Only trip organizers can perform this action.');
        }

        return $next($request);
    }
}
