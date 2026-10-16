@extends('layouts.admin')

@section('page-title', 'Add User')
@section('page-subtitle', 'Create a new user account')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .add-user-page {
        font-family: 'Poppins', sans-serif;
        color: var(--au-text, #111827);
        background: var(--au-page, #f1f1ee) !important;
        min-height: 100vh;
    }

    /* Theme colors follow the shared admin layout's data-theme setting. */
    :root {
        --au-page: #f1f1ee;
        --au-card: #ffffff;
        --au-text: #111827;
        --au-muted: #6b7280;
        --au-border: #e5e7eb;
        --au-input: #ffffff;
        --au-input-text: #111827;
        --au-placeholder: #6b7280;
        --au-info-bg: #f0fdf4;
        --au-info-border: #bbf7d0;
        --au-info-title: #166534;
        --au-info-text: #4b5563;
        --au-hover: #f9fafb;
    }

    html[data-theme="dark"] {
        --au-page: #0A0F0D;
        --au-card: #121A16;
        --au-text: #F3F4F6;
        --au-muted: #9CA3AF;
        --au-border: #1B3A2B;
        --au-input: #18241F;
        --au-input-text: #F3F4F6;
        --au-placeholder: #9CA3AF;
        --au-info-bg: #162E24;
        --au-info-border: #2D6A4F;
        --au-info-title: #76C893;
        --au-info-text: #D1D5DB;
        --au-hover: #162E24;
    }

    .add-user-card {
        background: var(--au-card);
        color: var(--au-text);
        border: 1px solid var(--au-border);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
        min-width: 0;
    }

    .add-user-page .border-gray-200 {
        border-color: var(--au-border) !important;
    }

    .add-user-back,
    .add-user-cancel {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 16px;
        border: 1px solid var(--au-border);
        border-radius: 7px;
        background: var(--au-card);
        color: var(--au-text);
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        line-height: 1.4;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .add-user-back:hover,
    .add-user-cancel:hover {
        background: var(--au-hover);
        border-color: #52B788;
        color: var(--au-text);
    }

    .add-user-back svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .add-user-section-title {
        font-size: 20px;
        line-height: 1.4;
        font-weight: 700;
        color: var(--au-text);
    }

    .add-user-section-description {
        font-size: 13px;
        line-height: 1.6;
        font-weight: 400;
        color: var(--au-muted);
    }

    .add-user-label {
        display: block;
        font-size: 14px;
        line-height: 1.5;
        font-weight: 600;
        color: var(--au-text);
        margin-bottom: 7px;
    }

    .add-user-input {
        display: block;
        width: 100%;
        min-width: 0;
        min-height: 46px;
        padding: 10px 13px;
        border: 1px solid var(--au-border);
        border-radius: 7px;
        background: var(--au-input);
        color: var(--au-input-text);
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        line-height: 1.5;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .add-user-input:focus {
        border-color: #52B788;
        box-shadow: 0 0 0 3px rgba(82, 183, 136, 0.16);
    }

    .add-user-input::placeholder {
        color: var(--au-placeholder);
        opacity: 1;
    }

    .add-user-input option {
        background: var(--au-input);
        color: var(--au-input-text);
    }

    .add-user-error {
        margin-top: 6px;
        font-size: 13px;
        line-height: 1.5;
        color: #ef4444;
    }

    .add-user-error-box {
        margin: 20px 24px 0;
        border: 1px solid #fecaca;
        background: #fef2f2;
        border-radius: 7px;
        padding: 14px 16px;
    }

    .add-user-error-title {
        font-size: 14px;
        line-height: 1.5;
        font-weight: 600;
        color: #b91c1c;
    }

    .add-user-error-list {
        margin-top: 5px;
        font-size: 13px;
        line-height: 1.6;
        color: #dc2626;
    }

    .add-user-info {
        background: var(--au-info-bg);
        border: 1px solid var(--au-info-border);
        border-radius: 7px;
        padding: 14px 16px;
    }

    .add-user-info-title {
        font-size: 14px;
        line-height: 1.5;
        font-weight: 600;
        color: var(--au-info-title);
    }

    .add-user-info-text {
        font-size: 13px;
        line-height: 1.6;
        color: var(--au-info-text);
    }

    .add-user-submit {
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
        line-height: 1.4;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .add-user-submit:hover {
        background: #256847;
    }

    .add-user-required {
        color: #ef4444;
    }

    /* Prevent form fields from overflowing when browser zoom reduces viewport width. */
    .add-user-page,
    .add-user-page * {
        box-sizing: border-box;
    }

    .add-user-page .grid {
        min-width: 0;
    }

    .add-user-page .grid > div {
        min-width: 0;
    }

    @media (max-width: 768px) {
        .add-user-page {
            padding: 16px !important;
        }

        .add-user-section-title {
            font-size: 18px;
        }

        .add-user-section-description {
            font-size: 13px;
        }

        .add-user-label,
        .add-user-input {
            font-size: 14px;
        }

        .add-user-back {
            width: 100%;
        }

        .add-user-error-box {
            margin-left: 16px;
            margin-right: 16px;
        }

        .add-user-page form {
            padding: 16px !important;
        }

        .add-user-page form > .grid {
            gap: 18px;
        }

        .add-user-page form > div:last-child {
            flex-wrap: wrap;
        }

        .add-user-cancel,
        .add-user-submit {
            min-height: 44px;
            font-size: 14px;
        }
    }
</style>


<div class="add-user-page bg-[#f1f1ee] min-h-screen p-5 md:p-6 -mx-6 -mt-6 pb-10 w-[calc(100%_+_3rem)]">

    <div class="max-w-5xl mx-auto">

        <!-- =========================================
             BACK TO USER MANAGEMENT
        ========================================== -->

        <div class="mb-4">

            <a
                href="{{ route('admin.users') }}"
                class="add-user-back"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                    />
                </svg>

                <span>
                    Back to User Management
                </span>

            </a>

        </div>


        <!-- =========================================
             MAIN CARD
        ========================================== -->

        <div class="add-user-card overflow-hidden">

            <!-- Header -->

            <div class="px-6 py-6 border-b border-gray-200">

                <h2 class="add-user-section-title">
                    User Information
                </h2>

                <p class="add-user-section-description mt-1">
                    Enter the details below to create a new CarbonWise user account.
                </p>

            </div>


            <!-- =========================================
                 VALIDATION ERRORS
            ========================================== -->

            @if($errors->any())

                <div class="add-user-error-box">

                    <p class="add-user-error-title">
                        Please check the following:
                    </p>

                    <ul class="list-disc list-inside add-user-error-list">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <!-- =========================================
                 USER FORM
            ========================================== -->

            <form
                method="POST"
                action="{{ route('admin.users.store') }}"
                class="p-6"
            >

                @csrf


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


                    <!-- Full Name -->

                    <div>

                        <label
                            for="name"
                            class="add-user-label block mb-1.5"
                        >
                            Full Name
                            <span class="add-user-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="add-user-input"
                            placeholder="Enter full name"
                            required
                        >

                        @error('name')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- G-Suite Email -->

                    <div>

                        <label
                            for="email"
                            class="add-user-label block mb-1.5"
                        >
                            G-Suite Email
                            <span class="add-user-required">*</span>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="add-user-input"
                            placeholder="example@g.batstate-u.edu.ph"
                            required
                        >

                        @error('email')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- SR Code -->

                    <div>

                        <label
                            for="sr_code"
                            class="add-user-label block mb-1.5"
                        >
                            SR Code
                        </label>

                        <input
                            type="text"
                            id="sr_code"
                            name="sr_code"
                            value="{{ old('sr_code') }}"
                            class="add-user-input"
                            placeholder="Enter SR Code"
                        >

                        @error('sr_code')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- Role -->

                    <div>

                        <label
                            for="role"
                            class="add-user-label block mb-1.5"
                        >
                            Role
                            <span class="add-user-required">*</span>
                        </label>

                        <select
                            id="role"
                            name="role"
                            class="add-user-input"
                            required
                        >

                            <option value="">
                                Select role
                            </option>

                            <option
                                value="student"
                                {{ old('role') == 'student' ? 'selected' : '' }}
                            >
                                Student
                            </option>

                            <option
                                value="faculty"
                                {{ old('role') == 'faculty' ? 'selected' : '' }}
                            >
                                Faculty
                            </option>

                            <option
                                value="staff"
                                {{ old('role') == 'staff' ? 'selected' : '' }}
                            >
                                Staff
                            </option>

                            <option
                                value="admin"
                                {{ old('role') == 'admin' ? 'selected' : '' }}
                            >
                                Admin
                            </option>

                        </select>

                        @error('role')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- Department -->

                    <div>

                        <label
                            for="department"
                            class="add-user-label block mb-1.5"
                        >
                            Department / College
                            <span class="add-user-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="department"
                            name="department"
                            value="{{ old('department') }}"
                            class="add-user-input"
                            placeholder="Enter department or college"
                            required
                        >

                        @error('department')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- Campus -->

                    <div>

                        <label
                            for="campus"
                            class="add-user-label block mb-1.5"
                        >
                            Campus
                        </label>

                        <select
                            id="campus"
                            name="campus"
                            class="add-user-input"
                        >

                            <option value="">
                                Select campus
                            </option>

                            <option
                                value="Lipa Campus"
                                {{ old('campus') == 'Lipa Campus' ? 'selected' : '' }}
                            >
                                Lipa Campus
                            </option>

                            <option
                                value="Alangilan Campus"
                                {{ old('campus') == 'Alangilan Campus' ? 'selected' : '' }}
                            >
                                Alangilan Campus
                            </option>

                            <option
                                value="Pablo Borbon Campus"
                                {{ old('campus') == 'Pablo Borbon Campus' ? 'selected' : '' }}
                            >
                                Pablo Borbon Campus
                            </option>

                            <option
                                value="ARASOF Nasugbu Campus"
                                {{ old('campus') == 'ARASOF Nasugbu Campus' ? 'selected' : '' }}
                            >
                                ARASOF Nasugbu Campus
                            </option>

                            <option
                                value="Rosario Campus"
                                {{ old('campus') == 'Rosario Campus' ? 'selected' : '' }}
                            >
                                Rosario Campus
                            </option>

                            <option
                                value="Balayan Campus"
                                {{ old('campus') == 'Balayan Campus' ? 'selected' : '' }}
                            >
                                Balayan Campus
                            </option>

                            <option
                                value="Lemery Campus"
                                {{ old('campus') == 'Lemery Campus' ? 'selected' : '' }}
                            >
                                Lemery Campus
                            </option>

                            <option
                                value="San Juan Campus"
                                {{ old('campus') == 'San Juan Campus' ? 'selected' : '' }}
                            >
                                San Juan Campus
                            </option>

                            <option
                                value="Malvar Campus"
                                {{ old('campus') == 'Malvar Campus' ? 'selected' : '' }}
                            >
                                Malvar Campus
                            </option>

                        </select>

                        @error('campus')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- Status -->

                    <div>

                        <label
                            for="status"
                            class="add-user-label block mb-1.5"
                        >
                            Status
                            <span class="add-user-required">*</span>
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="add-user-input"
                            required
                        >

                            <option value="">
                                Select status
                            </option>

                            <option
                                value="Active"
                                {{ old('status', 'Active') == 'Active' ? 'selected' : '' }}
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                {{ old('status') == 'Inactive' ? 'selected' : '' }}
                            >
                                Inactive
                            </option>

                            <option
                                value="Pending"
                                {{ old('status') == 'Pending' ? 'selected' : '' }}
                            >
                                Pending
                            </option>

                        </select>

                        @error('status')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- Temporary Password -->

                    <div>

                        <label
                            for="password"
                            class="add-user-label block mb-1.5"
                        >
                            Temporary Password
                            <span class="add-user-required">*</span>
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="add-user-input"
                            placeholder="Enter temporary password"
                            required
                        >

                        @error('password')

                            <p class="add-user-error">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    <!-- Confirm Password -->

                    <div>

                        <label
                            for="password_confirmation"
                            class="add-user-label block mb-1.5"
                        >
                            Confirm Password
                            <span class="add-user-required">*</span>
                        </label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="add-user-input"
                            placeholder="Confirm temporary password"
                            required
                        >

                    </div>

                </div>


                <!-- =========================================
                     ACCOUNT INFORMATION
                ========================================== -->

                <div class="add-user-info mt-6">

                    <p class="add-user-info-title">
                        Account Information
                    </p>

                    <p class="add-user-info-text mt-1">
                        The user's G-Suite email will be used as their account identifier.
                        Make sure the email address is correct before creating the account.
                    </p>

                </div>


                <!-- =========================================
                     FORM ACTIONS
                ========================================== -->

                <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-gray-200">

                    <a
                        href="{{ route('admin.users') }}"
                        class="add-user-cancel inline-flex items-center justify-center"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="add-user-submit"
                    >
                        Add User
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================
     SUCCESS MESSAGE
========================================== -->

@if(session('success'))

    <div
        id="user-success-message"
        data-message="{{ session('success') }}"
        class="hidden"
    ></div>

@endif


<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const successMessage =
                document.getElementById('user-success-message');


            if (successMessage) {

                Swal.fire({

                    icon: 'success',

                    title: 'User Added',

                    text: successMessage.dataset.message,

                    confirmButtonColor: '#2f7d57',

                    confirmButtonText: 'OK'

                });

            }

        }
    );

</script>


@endsection