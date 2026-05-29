<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Handle an incoming Google Identity Services OAuth callback.
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $idToken = $request->input('credential');

        if (!$idToken) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google authentication failed: Credential not provided.'
            ]);
        }

        try {
            // Verify ID Token securely against Google's tokeninfo API
            $response = Http::get("https://oauth2.googleapis.com/tokeninfo", [
                    'id_token' => $idToken
                ]);

            if ($response->failed()) {
                Log::error('Google token verification failed', [
                    'error' => $response->body()
                ]);
                return redirect()->route('login')->withErrors([
                    'google' => 'Google authentication failed: Invalid token verification.'
                ]);
            }

            $payload = $response->json();

            // Validate that the audience matches our client ID
            $clientId = config('services.google.client_id');
            if (isset($payload['aud']) && $payload['aud'] !== $clientId) {
                Log::error('Google client ID mismatch', [
                    'aud' => $payload['aud'],
                    'configured' => $clientId
                ]);
                return redirect()->route('login')->withErrors([
                    'google' => 'Google authentication failed: Client ID mismatch.'
                ]);
            }

            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? null;
            $picture = $payload['picture'] ?? null;

            if (!$email) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Google authentication failed: Email not retrieved.'
                ]);
            }

            // Find or create user
            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $name ?? 'Google User',
                    'email' => $email,
                    'password' => bcrypt(Str::random(16)),
                    'avatar' => $picture,
                ]);
            } else {
                // Update avatar if empty
                if (!$user->avatar && $picture) {
                    $user->avatar = $picture;
                    $user->save();
                }
            }

            // Log user in
            Auth::login($user, remember: true);

            // Replicate standard Breeze session regeneration and pending redirect checks
            $request->session()->regenerate();

            if ($token = $request->session()->get('invitation_token')) {
                return redirect()->route('invitation.accept', ['token' => $token]);
            }

            if ($code = $request->session()->get('pending_join_code')) {
                return redirect()->route('trips.join', ['code' => $code]);
            }

            return redirect()->intended(route('dashboard', absolute: false));

        } catch (\Exception $e) {
            Log::error('Google auth callback exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')->withErrors([
                'google' => 'Google authentication failed: An unexpected error occurred.'
            ]);
        }
    }
}
