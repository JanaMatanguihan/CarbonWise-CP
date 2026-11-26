
@extends('layouts.admin')

@section('page-title', 'Edit User')
@section('page-subtitle', 'User Management > Edit User')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .edit-user-page {
        font-family: 'Poppins', sans-serif;
        color: var(--eu-text, #111827);
        background: var(--eu-page, #f1f1ee) !important;
        min-height: 100vh;
    }

    /* Theme colors follow the shared admin layout's data-theme setting. */
    :root {
        --eu-page: #f1f1ee;
        --eu-card: #ffffff;
        --eu-text: #111827;
        --eu-muted: #6b7280;
        --eu-border: #e5e7eb;
        --eu-input: #ffffff;
        --eu-input-text: #111827;
        --eu-placeholder: #6b7280;
        --eu-hover: #f9fafb;
        --eu-disabled: #f3f4f6;
    }

    html[data-theme="dark"] {
        --eu-page: #0A0F0D;
        --eu-card: #121A16;
        --eu-text: #F3F4F6;
        --eu-muted: #9CA3AF;
        --eu-border: #1B3A2B;
        --eu-input: #18241F;
        --eu-input-text: #F3F4F6;
        --eu-placeholder: #9CA3AF;
        --eu-hover: #162E24;
        --eu-disabled: #18241F;
    }

    .edit-user-card {
        background: var(--eu-card);
        color: var(--eu-text);
        border: 1px solid var(--eu-border);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
        min-width: 0;
    }

    .edit-user-page,
    .edit-user-page * {
        box-sizing: border-box;
    }

    .edit-user-page .border-gray-200 {
        border-color: var(--eu-border) !important;
    }

    .edit-user-title {
        font-size: 20px;
        line-height: 1.4;
        font-weight: 700;
        color: var(--eu-text);
    }

    .edit-user-description {
        font-size: 13px;
        line-height: 1.6;
        color: var(--eu-muted);
    }

    .edit-user-label {
        display: block;
        font-size: 14px;
        line-height: 1.5;
        font-weight: 600;
        color: var(--eu-text);
        margin-bottom: 7px;
    }

    .edit-user-input {
        display: block;
        width: 100%;
        min-width: 0;
        min-height: 46px;
        padding: 10px 13px;
        border: 1px solid var(--eu-border);
        border-radius: 7px;
        background: var(--eu-input);
        color: var(--eu-input-text);
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        line-height: 1.5;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .edit-user-input:focus {
        border-color: #52B788;
        box-shadow: 0 0 0 3px rgba(82, 183, 136, 0.16);
    }

    .edit-user-input:disabled,
    .edit-user-input[readonly] {
        background: var(--eu-disabled);
        color: var(--eu-muted);
        cursor: not-allowed;
    }

    .edit-user-input option {
        background: var(--eu-input);
        color: var(--eu-input-text);
    }

    .edit-user-error {
        margin-top: 6px;
        font-size: 13px;
        line-height: 1.5;
        color: #ef4444;
    }

    .edit-user-cancel {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 22px;
        border: 1px solid var(--eu-border);
        border-radius: 7px;
        background: var(--eu-card);
        color: var(--eu-text);
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .edit-user-cancel:hover {
        background: var(--eu-hover);
        border-color: #52B788;
        color: var(--eu-text);
    }

    .edit-user-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 22px;
        border: none;
        border-radius: 7px;
        background: #2f7d57;
        color: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .edit-user-submit:hover {
        background: #256847;
    }

    .edit-user-required {
        color: #ef4444;
    }

    .edit-user-page .grid {
        min-width: 0;
    }

    .edit-user-page .grid > div {
        min-width: 0;
    }

    @media (max-width: 768px) {
        .edit-user-page {
            padding: 16px !important;
        }

        .edit-user-title {
            font-size: 18px;
        }

        .edit-user-label,
        .edit-user-input {
            font-size: 14px;
        }

        .edit-user-page form {
            padding: 16px !important;
        }

        .edit-user-page form > .grid {
            gap: 18px;
        }

        .edit-user-page form > div:last-child {
            flex-wrap: wrap;
        }

        .edit-user-cancel,
        .edit-user-submit {
            min-height: 44px;
            font-size: 14px;
        }
    }
</style>

<div class="edit-user-page bg-[#f1f1ee] min-h-screen p-5 md:p-6 -mx-6 -mt-6 pb-10 w-[calc(100%_+_3rem)]">

    <div class="max-w-5xl mx-auto">

        {{-- Main Card --}}
        <div class="edit-user-card overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-6 border-b border-gray-200">

                <h2 class="edit-user-title">
                    User Information
                </h2>

                <p class="edit-user-description mt-1">
                    Update the details of this CarbonWise user account.
                </p>

            </div>

            {{-- Validation Errors --}}
            @if($errors->any())

                <div class="mx-6 mt-5 border border-red-300 bg-red-50 rounded-lg p-4">

                    <p class="text-sm font-semibold text-red-700">
                        Please check the following:
                    </p>

                    <ul class="list-disc list-inside mt-1 text-sm text-red-600">

                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif

            {{-- Edit Form --}}
            <form
                action="{{ route('admin.users.update', $user->g_suite) }}"
                method="POST"
                class="p-6"
            >

                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Full Name --}}
                    <div>

                        <label for="full_name" class="edit-user-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="{{ old('full_name', $user->full_name) }}"
                            class="edit-user-input"
                        >

                        @error('full_name')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- G Suite --}}
                    <div>

                        <label for="g_suite" class="edit-user-label">
                            G-Suite Email
                        </label>

                        <input
                            type="email"
                            id="g_suite"
                            value="{{ $user->g_suite }}"
                            class="edit-user-input"
                            readonly
                        >

                        <input
                            type="hidden"
                            name="g_suite"
                            value="{{ $user->g_suite }}"
                        >

                        @error('g_suite')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- SR Code --}}
                    <div>

                        <label for="sr_code" class="edit-user-label">
                            SR Code
                        </label>

                        <input
                            type="text"
                            id="sr_code"
                            name="sr_code"
                            value="{{ old('sr_code', $user->sr_code) }}"
                            class="edit-user-input"
                        >

                        @error('sr_code')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- Campus --}}
                    <div>

                        <label for="campus" class="edit-user-label">
                            Campus
                        </label>

                        <input
                            type="text"
                            id="campus"
                            name="campus"
                            value="{{ old('campus', $user->campus) }}"
                            class="edit-user-input"
                        >

                        @error('campus')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- Department --}}
                    <div>

                        <label for="department" class="edit-user-label">
                            Department / College
                        </label>

                        <input
                            type="text"
                            id="department"
                            name="department"
                            value="{{ old('department', $user->department) }}"
                            class="edit-user-input"
                        >

                        @error('department')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- Year Level --}}
                    <div>

                        <label for="year_level" class="edit-user-label">
                            Year Level
                        </label>

                        <input
                            type="number"
                            id="year_level"
                            name="year_level"
                            value="{{ old('year_level', $user->year_level) }}"
                            class="edit-user-input"
                        >

                        @error('year_level')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- Role --}}
                    <div>

                        <label for="role" class="edit-user-label">
                            Role
                        </label>

                        <select
                            id="role"
                            name="role"
                            class="edit-user-input"
                        >

                            <option value="student" {{ old('role', $user->role) == 'student' ? 'selected' : '' }}>
                                Student
                            </option>

                            <option value="faculty" {{ old('role', $user->role) == 'faculty' ? 'selected' : '' }}>
                                Faculty
                            </option>

                            <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>
                                Staff
                            </option>

                        </select>

                        @error('role')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- Status --}}
                    <div>

                        <label for="status" class="edit-user-label">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="edit-user-input"
                        >

                            <option value="Active" {{ old('status', $user->status) == 'Active' ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="Inactive" {{ old('status', $user->status) == 'Inactive' ? 'selected' : '' }}>
                                Inactive
                            </option>

                        </select>

                        @error('status')
                            <p class="edit-user-error">{{ $message }}</p>
                        @enderror

                    </div>

                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-gray-200">

                    <a
                        href="{{ route('admin.users.show', $user->g_suite) }}"
                        class="edit-user-cancel"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="edit-user-submit"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection
