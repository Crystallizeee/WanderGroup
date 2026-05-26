<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Trip;

/**
 * Ensures the authenticated user is a member of the trip.
 * Loads the trip and injects it into the request.
 */
class EnsureTripMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $trip = $request->route('trip');

        // If route binding hasn't happened yet, $trip will be an ID
        if (is_string($trip) || is_numeric($trip)) {
            $trip = Trip::find($trip);
        }

        if (!$trip instanceof Trip) {
            abort(404, 'Trip not found.');
        }

        $user = $request->user();

        // Single query to check membership and get role
        $membership = $trip->members()->where('user_id', $user->id)->first();

        if (!$membership) {
            abort(403, 'You are not a member of this trip.');
        }

        // Inject user's role in this trip into the request
        $request->merge(['trip_role' => $membership->pivot->role]);

        return $next($request);
    }
}
