@extends('layouts.admin')

@section('page-title', 'Settings')
@section('page-subtitle', 'Manage system preferences and configuration')

@section('content')

<div class="bg-[#f3f4f6] min-h-screen -mx-6 -mt-6 p-6">

<form method="POST" action="{{ route('admin.settings.update') }}">

@csrf
@method('PUT')

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Navigation Tabs --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6">

        <div class="flex gap-10 h-16 items-center">

            <button type="button" class="font-semibold text-green-700 border-b-2 border-green-700 h-full">
                General
            </button>

            <button type="button" class="text-gray-500">
                Profile
            </button>

            <button type="button" class="text-gray-500">
                Security
            </button>

            <button type="button" class="text-gray-500">
                Notifications
            </button>

            <button type="button" class="text-gray-500">
                System
            </button>

            <button type="button" class="text-gray-500">
                Backup & Maintenance
            </button>

        </div>

    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-12 gap-6">

        {{-- LEFT COLUMN --}}
        <div class="col-span-7">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-7">

                <h2 class="text-2xl font-bold">
                    General Settings
                </h2>

                <p class="text-gray-500 mb-8">
                    Configure general system settings and preferences.
                </p>

                <div class="space-y-5">

                    <div>
                        <label class="text-sm text-gray-600">System Name</label>

                        <input
                            name="system_name"
                            value="{{ old('system_name',$setting->system_name) }}"
                            class="w-full mt-2 rounded-lg border-gray-300">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Organization</label>

                        <input
                            name="organization"
                            value="{{ old('organization',$setting->organization) }}"
                            class="w-full mt-2 rounded-lg border-gray-300">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Email</label>

                        <input
                            name="email"
                            value="{{ old('email',$setting->email) }}"
                            class="w-full mt-2 rounded-lg border-gray-300">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Phone</label>

                        <input
                            name="phone"
                            value="{{ old('phone',$setting->phone) }}"
                            class="w-full mt-2 rounded-lg border-gray-300">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Timezone</label>

                      <select
                            name="timezone"
                            class="w-full mt-2 rounded-lg border-gray-300">

                            @foreach([
                                'Asia/Manila',
                                'Asia/Tokyo',
                                'UTC'
                            ] as $timezone)

                                <option
                                    value="{{ $timezone }}"
                                    {{ old('timezone', $setting->timezone) == $timezone ? 'selected' : '' }}>
                                    {{ $timezone }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Date Format</label>

                        <select
                            name="date_format"
                            class="w-full mt-2 rounded-lg border-gray-300">

                            @foreach([
                                'F d, Y',
                                'M d, Y',
                                'd/m/Y',
                                'Y-m-d'
                            ] as $format)

                                <option
                                    value="{{ $format }}"
                                    {{ old('date_format', $setting->date_format) == $format ? 'selected' : '' }}>

                                    {{ $format }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Language</label>

                       <select
                            name="language"
                            class="w-full mt-2 rounded-lg border-gray-300">

                            @foreach([
                                'English',
                                'Filipino'
                            ] as $language)

                                <option
                                    value="{{ $language }}"
                                    {{ old('language', $setting->language) == $language ? 'selected' : '' }}>

                                    {{ $language }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

                 <button
                    id="saveButton"
                    type="submit"
                    class="mt-8 text-white px-8 py-3 rounded-lg transition"
                    style="background-color: var(--accent-color);">

                    Save Changes

                </button>

            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-span-5 space-y-6">

            {{-- Appearance --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">

                <h2 class="text-xl font-bold">
                    System Appearance
                </h2>

                <p class="text-gray-500 mb-6">
                    Customize the look and feel of the system.
                </p>

                <label class="font-medium">
                    Theme
                </label>

                <input
                type="hidden"
                id="theme"
                name="theme"
                value="{{ old('theme', $setting->theme) }}">

                    <div class="grid grid-cols-3 gap-3 mt-3">

                        <button
                            type="button"
                            class="theme-btn rounded-xl border-2 border-gray-300 p-5 transition"
                            data-theme="light">
                            ☀️<br>Light
                        </button>

                        <button
                            type="button"
                            class="theme-btn rounded-xl border-2 border-gray-300 p-5 transition"
                            data-theme="dark">
                            🌙<br>Dark
                        </button>

                        <button
                            type="button"
                            class="theme-btn rounded-xl border-2 border-gray-300 p-5 transition"
                            data-theme="system">
                            💻<br>System
                        </button>

                    </div>

                <label class="block mt-6 font-medium">
                    Primary Color
                </label>

                <input
                type="hidden"
                id="accent_color"
                name="accent_color"
                value="{{ old('accent_color', $setting->accent_color) }}">

                <div class="flex gap-3 mt-3">

                        <button
                            type="button"
                            class="color-btn w-10 h-10 rounded-full bg-green-700"
                            data-color="#15803d">
                        </button>

                        <button
                            type="button"
                            class="color-btn w-10 h-10 rounded-full bg-blue-600"
                            data-color="#2563eb">
                        </button>

                        <button
                            type="button"
                            class="color-btn w-10 h-10 rounded-full bg-cyan-500"
                            data-color="#06b6d4">
                        </button>

                        <button
                            type="button"
                            class="color-btn w-10 h-10 rounded-full bg-purple-600"
                            data-color="#9333ea">
                        </button>

                        <button
                            type="button"
                            class="color-btn w-10 h-10 rounded-full bg-orange-500"
                            data-color="#f97316">
                        </button>

                        <button
                            type="button"
                            class="color-btn w-10 h-10 rounded-full bg-red-600"
                            data-color="#dc2626">
                        </button>

                        <button
                            type="button"
                            class="color-btn w-10 h-10 rounded-full bg-gray-700"
                            data-color="#374151">
                        </button>

                    </div>
            </div>

            {{-- Session --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">

                <h2 class="text-xl font-bold mb-5">
                    Session Settings
                </h2>

                <div class="grid grid-cols-2 gap-4">

                    <div>

                        <label>Session Timeout</label>

                       <select
                            name="session_timeout"
                            class="w-full mt-2 rounded-lg border-gray-300">

                            @foreach([15, 30, 60, 120] as $timeout)

                                <option
                                    value="{{ $timeout }}"
                                    {{ old('session_timeout', $setting->session_timeout) == $timeout ? 'selected' : '' }}>

                                    {{ $timeout }} Minutes

                                </option>

                            @endforeach

                        </select>
                    </div>

                    <div>

                        <label>Remember Me</label>

                        <select
                            name="remember_days"
                            class="w-full mt-2 rounded-lg border-gray-300">

                            <option value="1" {{ $setting->remember_days == 1 ? 'selected' : '' }}>
                                1 Day
                            </option>

                            <option value="3" {{ $setting->remember_days == 3 ? 'selected' : '' }}>
                                3 Days
                            </option>

                            <option value="7" {{ $setting->remember_days == 7 ? 'selected' : '' }}>
                                7 Days
                            </option>

                            <option value="14" {{ $setting->remember_days == 14 ? 'selected' : '' }}>
                                14 Days
                            </option>

                            <option value="30" {{ $setting->remember_days == 30 ? 'selected' : '' }}>
                                30 Days
                            </option>

                        </select>
                    </div>

                </div>

            </div>

            {{-- Data Preferences --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">

                <h2 class="text-xl font-bold mb-5">
                    Data Preferences
                </h2>

                <div class="grid grid-cols-2 gap-4">

                    <div>

                        <label>Default Dashboard</label>

                        <input
                            name="default_dashboard"
                            value="{{ old('default_dashboard',$setting->default_dashboard) }}"
                            class="w-full mt-2 rounded-lg border-gray-300">

                    </div>

                    <div>

                        <label>Records Per Page</label>

                        <input
                            type="number"
                            name="records_per_page"
                            value="{{ old('records_per_page',$setting->records_per_page) }}"
                            class="w-full mt-2 rounded-lg border-gray-300">

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</form>

</div>

@push('scripts')

<script>

const themeInput = document.getElementById('theme');

document.querySelectorAll('.theme-btn').forEach(button => {

    // Highlight the saved theme when the page loads
    if (button.dataset.theme === themeInput.value) {

        button.classList.add(
            'border-green-600',
            'bg-green-50'
        );

    } else {

        button.classList.add('border-gray-300');

    }

    button.addEventListener('click', function () {

        themeInput.value = this.dataset.theme;

        document.querySelectorAll('.theme-btn').forEach(btn => {

           btn.classList.remove(
                'border-green-600',
                'bg-green-50'
            );

            btn.classList.add(
                'border-gray-300'
            );

        });

       this.classList.remove('border-gray-300');

        this.classList.add(
            'border-green-600',
            'bg-green-50'
        );
    });

});

const accentInput = document.getElementById('accent_color');

document.querySelectorAll('.color-btn').forEach(button => {

    // Highlight the saved color when the page loads
    if (button.dataset.color === accentInput.value) {

        button.classList.add(
            'ring-4',
            'ring-offset-2',
            'ring-gray-400'
        );

    }

    button.addEventListener('click', function () {

        accentInput.value = this.dataset.color;

        document.documentElement.style.setProperty(
            '--accent-color',
            this.dataset.color
        );

        document.querySelectorAll('.color-btn').forEach(btn => {

            btn.classList.remove(
                'ring-4',
                'ring-offset-2',
                'ring-gray-400'
            );

        });

        this.classList.add(
            'ring-4',
            'ring-offset-2',
            'ring-gray-400'
        );

    });

});

const successMessage = "{{ session('success') }}";

if (successMessage) {

    Swal.fire({

        icon: 'success',

        title: 'Settings Saved',

        text: successMessage,

        confirmButtonColor: '#15803d',

        timer: 2200,

        showConfirmButton: false

    });

}

</script>

@endpush
@endsection