<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $setting = Setting::firstOrFail();

        return view('admin.settings', compact('setting'));
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'system_name' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'timezone' => 'required|string|max:255',
            'date_format' => 'required|string|max:255',
            'language' => 'required|string|max:255',
            'theme' => 'required|string|max:255',
            'accent_color' => 'required|string|max:20',
            'session_timeout' => 'required|integer|min:5',
            'remember_days' => 'required|integer|min:1',
            'default_dashboard' => 'required|string|max:255',
            'records_per_page' => 'required|integer|min:5',
        ]);

        $setting = Setting::firstOrFail();

        $setting->update($validated);

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Settings updated successfully.');
    }
}