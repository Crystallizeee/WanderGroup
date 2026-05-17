<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:5120'], // 5MB max
            'phone_number' => ['nullable', 'string', 'max:30'],
            'language' => ['nullable', 'string', 'max:50'],
            'dietary_requirements' => ['nullable', 'array'],
            'dietary_requirements.*' => ['string', 'max:50'],
            'travel_style' => ['nullable', 'string', 'in:Adventure,Relaxing,Cultural,Luxury'],
        ]);

        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->has('bio')) {
            $user->bio = $request->input('bio');
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $prefs = $user->travel_preferences ?? [];
        $prefs['phone_number'] = $request->input('phone_number');
        $prefs['language'] = $request->input('language', 'English (US)');
        $prefs['dietary_requirements'] = $request->input('dietary_requirements', []);
        $prefs['travel_style'] = $request->input('travel_style', 'Adventure');
        $user->travel_preferences = $prefs;

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
