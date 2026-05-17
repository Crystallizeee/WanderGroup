<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\ActivityLog;
use App\Mail\TripInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * Send trip invitations by email.
     */
    public function invite(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'emails' => ['required', 'string'], // comma-separated
        ]);

        $emails = array_map('trim', explode(',', $validated['emails']));
        $invited = 0;

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Skip if already a member with this email
            $existingMember = $trip->members()->where('email', $email)->exists();
            if ($existingMember) {
                continue;
            }

            // Skip if already invited and pending
            if (TripInvitation::where('trip_id', $trip->id)
                ->where('email', $email)
                ->where('status', 'pending')
                ->exists()) {
                continue;
            }

            $invitation = TripInvitation::create([
                'trip_id' => $trip->id,
                'email' => $email,
                'token' => Str::random(64),
                'status' => 'pending',
                'invited_by' => Auth::id(),
                'expires_at' => now()->addDays(7),
            ]);

            // Send Email
            Mail::to($email)->send(new TripInvitationMail($invitation));

            $invited++;
        }

        ActivityLog::log($trip->id, Auth::id(), 'sent_invitations', Trip::class, $trip->id, [
            'count' => $invited,
        ]);

        return back()->with('success', "{$invited} invitation(s) sent!");
    }

    /**
     * Accept an invitation via token.
     */
    public function accept(string $token)
    {
        $invitation = TripInvitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            return redirect()->route('home')
                ->with('error', 'This invitation has expired.');
        }

        $user = Auth::user();

        if (!$user) {
            // Store token in session and redirect to register
            session(['invitation_token' => $token]);
            return redirect()->route('register')
                ->with('info', 'Create an account to join the trip!');
        }

        // Check if already a member
        if ($invitation->trip->isMember($user)) {
            $invitation->update(['status' => 'accepted']);
            return redirect()->route('trips.show', $invitation->trip)
                ->with('info', 'You are already a member of this trip.');
        }

        // Join the trip
        $invitation->trip->members()->attach($user->id, ['role' => 'member']);
        $invitation->update(['status' => 'accepted']);

        ActivityLog::log($invitation->trip_id, $user->id, 'joined_trip', Trip::class, $invitation->trip_id, [
            'title' => $invitation->trip->title,
        ]);

        return redirect()->route('trips.show', $invitation->trip)
            ->with('success', "Welcome to '{$invitation->trip->title}'!");
    }

    /**
     * Decline an invitation.
     */
    public function decline(string $token)
    {
        $invitation = TripInvitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->update(['status' => 'declined']);

        return redirect()->route('home')
            ->with('info', 'Invitation declined.');
    }

    /**
     * Join a trip using a public invite code.
     */
    public function join(string $code)
    {
        $trip = Trip::where('invite_code', $code)->firstOrFail();
        $user = Auth::user();

        if (!$user) {
            session(['pending_join_code' => $code]);
            return redirect()->route('register')
                ->with('info', 'Buat akun untuk bergabung ke trip ini!');
        }

        if ($trip->isMember($user)) {
            return redirect()->route('trips.show', $trip)
                ->with('info', 'Kamu sudah bergabung di trip ini.');
        }

        $trip->members()->attach($user->id, ['role' => 'member']);

        ActivityLog::log($trip->id, $user->id, 'joined_trip_via_code', Trip::class, $trip->id, [
            'title' => $trip->title,
        ]);

        return redirect()->route('trips.show', $trip)
            ->with('success', "Selamat datang di '{$trip->title}'!");
    }
}
