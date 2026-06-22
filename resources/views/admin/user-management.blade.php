@extends('layouts.admin')

@section('page-title', 'User Management')
@section('page-subtitle', 'Manage and monitor all system users')

@section('content')

<div class="mt-6 bg-white rounded-xl shadow overflow-hidden">

    <form
    method="GET"
    id="filterForm"
    class="flex items-center gap-4 pl-16 pr-6 py-5 border-b"
>

    <!-- Search -->
    <div class="relative w-48">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"
            />
        </svg>

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search users..."
            class="w-full border rounded-lg py-2 pl-10 pr-4"
            onkeydown="if(event.key==='Enter'){document.getElementById('filterForm').submit();}"
        >
    </div>

    <!-- BIG SPACE AFTER SEARCH -->
    <div class="flex items-center gap-4 ml-20">

        <!-- Role -->
        <select
        name="role"
        class="ml-16 border rounded-lg px-4 py-2 w-38"
        onchange="document.getElementById('filterForm').submit();"
        >
            <option value="">All Roles</option>
            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
            <option value="faculty" {{ request('role') == 'faculty' ? 'selected' : '' }}>Faculty</option>
            <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Student</option>
            <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
        </select>

        <!-- Department -->
        <select
            name="department"
            class="border rounded-lg px-4 py-2 w-48"
            onchange="document.getElementById('filterForm').submit();"
        >
            <option value="">All Departments</option>
            <option value="College of Teacher Education" {{ request('department') == 'College of Teacher Education' ? 'selected' : '' }}>CTE</option>
            <option value="College of Accountancy, Business, and Economics" {{ request('department') == 'College of Accountancy, Business, and Economics' ? 'selected' : '' }}>CABE</option>
            <option value="College of Arts and Sciences" {{ request('department') == 'College of Arts and Sciences' ? 'selected' : '' }}>CAS</option>
            <option value="College of Informatics and Computing Science" {{ request('department') == 'College of Informatics and Computing Science' ? 'selected' : '' }}>CICS</option>
            <option value="Admin Offices" {{ request('department') == 'Admin Offices' ? 'selected' : '' }}>Admin Offices</option>
            <option value="SDO Office" {{ request('department') == 'SDO Office' ? 'selected' : '' }}>SDO Office</option>
        </select>

        <!-- Status -->
        <select
            name="status"
            class="border rounded-lg px-4 py-2 w-32"
            onchange="document.getElementById('filterForm').submit();"
        >
            <option value="">All Status</option>
            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
        </select>

        <!-- Add User -->
        <a
            href="{{ route('register') }}"
            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold"
        >
            + Add User
        </a>

    </div>

</form>

    <div class="p-6">
        <table class="w-full">
            <thead class="border-b">
            <tr>
                <th class="text-left p-4">User</th>
                <th class="text-left p-4">Role</th>
                <th class="text-left p-4">Department / College</th>
                <th class="text-left p-4">Status</th>
                <th class="text-left p-4">Joined</th>
                <th class="text-left p-4">Actions</th>
            </tr>
        </thead>
            <tbody>
            @foreach ($users as $user)
            <tr class="border-b">
                <td class="p-4">
    <div class="flex items-center gap-3">

        @if ($user->profile_photo)
            <img
                src="{{ asset('storage/' . $user->profile_photo) }}"
                alt="{{ $user->name }}"
                class="w-12 h-12 rounded-full object-cover"
            >
        @else
            <img
                src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=16a34a&color=ffffff"
                alt="{{ $user->name }}"
                class="w-12 h-12 rounded-full"
            >
        @endif

        <div>
            <div class="font-semibold">
                {{ $user->name }}
            </div>

            <div class="text-sm text-gray-500">
                {{ $user->email }}
            </div>
        </div>

    </div>
</td>

                <td class="p-4">
                    {{ $user->role }}
                </td>

                <td class="p-4">
                    {{ $user->department }}
                </td>

                <td class="p-4">
                    @if($user->status === 'Active')
                        <span class="px-3 py-1 rounded bg-green-100 text-green-700">
                            Active
                        </span>
                    @else
                        <span class="px-3 py-1 rounded bg-red-100 text-red-700">
                            Inactive
                        </span>
                    @endif
                </td>

                <td class="p-4">
                    {{ $user->created_at->format('F d, Y') }}
                </td>

                <td class="p-4 text-center">
                    ⋮
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endsection