<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use DateTimeZone;
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
        $allTimezones = DateTimeZone::listIdentifiers();
        $usPrimaryTimezones = [
            'America/Los_Angeles',
            'America/Denver',
            'America/Chicago',
            'America/New_York',
            'America/Anchorage',
            'America/Adak',
            'Pacific/Honolulu',
        ];
        $usTimezones = array_values(array_intersect($usPrimaryTimezones, $allTimezones));
        $otherTimezones = array_values(array_diff($allTimezones, $usTimezones));

        return view('profile.edit', [
            'user' => $request->user(),
            'timezones' => array_merge($usTimezones, $otherTimezones),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
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
