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

    
    <div class="flex items-center gap-4 ml-20">

        <!-- Role -->
        <select
        name="role"
        class="ml-16 border rounded-lg px-4 py-2 w-40"
        onchange="document.getElementById('filterForm').submit();"
        >
            <option value="">All Roles</option>
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

                    <option value="CICS" {{ request('department') == 'CICS' ? 'selected' : '' }}>
                        CICS
                    </option>

                    <option value="CET" {{ request('department') == 'CET' ? 'selected' : '' }}>
                        CET
                    </option>

                    <option value="CAS" {{ request('department') == 'CAS' ? 'selected' : '' }}>
                        CAS
                    </option>

                    <option value="CABE" {{ request('department') == 'CABE' ? 'selected' : '' }}>
                        CABE
                    </option>

                    <option value="CTE" {{ request('department') == 'CTE' ? 'selected' : '' }}>
                        CTE
                    </option>
                </select>

                <!-- Campus -->
                <select
                    name="campus"
                    class="border rounded-lg px-4 py-2 w-52"
                    onchange="document.getElementById('filterForm').submit();"
                >
                    <option value="">All Campuses</option>

                    <option value="Lipa Campus" {{ request('campus') == 'Lipa Campus' ? 'selected' : '' }}>
                        Lipa Campus
                    </option>

                    <option value="Alangilan Campus" {{ request('campus') == 'Alangilan Campus' ? 'selected' : '' }}>
                        Alangilan Campus
                    </option>

                    <option value="Pablo Borbon Campus" {{ request('campus') == 'Pablo Borbon Campus' ? 'selected' : '' }}>
                        Pablo Borbon Campus
                    </option>

                    <option value="ARASOF Nasugbu Campus" {{ request('campus') == 'ARASOF Nasugbu Campus' ? 'selected' : '' }}>
                        ARASOF Nasugbu Campus
                    </option>

                    <option value="Rosario Campus" {{ request('campus') == 'Rosario Campus' ? 'selected' : '' }}>
                        Rosario Campus
                    </option>

                    <option value="Balayan Campus" {{ request('campus') == 'Balayan Campus' ? 'selected' : '' }}>
                        Balayan Campus
                    </option>

                    <option value="Lemery Campus" {{ request('campus') == 'Lemery Campus' ? 'selected' : '' }}>
                        Lemery Campus
                    </option>

                    <option value="San Juan Campus" {{ request('campus') == 'San Juan Campus' ? 'selected' : '' }}>
                        San Juan Campus
                    </option>

                    <option value="Malvar Campus" {{ request('campus') == 'Malvar Campus' ? 'selected' : '' }}>
                        Malvar Campus
                    </option>
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
                <th class="text-left p-4">Campus</th>
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

                        @if($user->profile_photo)
                            <img
                                src="{{ asset('storage/' . $user->profile_photo) }}"
                                class="w-12 h-12 rounded-full object-cover"
                            >
                        @else
                            <img
                                src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name) }}&background=16a34a&color=ffffff"
                                class="w-12 h-12 rounded-full"
                            >
                        @endif

                        <!-- User Details -->
                        <div class="flex flex-col">

                            <span class="font-semibold text-gray-900">
                                {{ $user->full_name }}
                            </span>

                            <span class="text-sm text-gray-500">
                                {{ $user->g_suite }}
                            </span>

                            <span class="text-xs text-gray-400">
                                {{ $user->sr_code }}
                            </span>

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
                    {{ $user->campus }}
                </td>

                <td class="p-4">
                    @if($user->status == 'Active')

                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm">
                            Active
                        </span>

                    @elseif($user->status == 'Pending')

                        <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm">
                            Pending
                        </span>

                    @else

                        <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm">
                            Inactive
                        </span>

                    @endif

                </td>

                <td class="p-4">
                    {{ $user->created_at ? $user->created_at->format('F d, Y') : 'N/A' }}
                </td>

                <td class="p-4 text-center relative action-menu">

            <button
                onclick="toggleMenu('{{ md5($user->g_suite) }}')"
                class="text-xl font-bold px-2 hover:text-green-600"
            >
                ⋮
            </button>

            <div
                id="menu-{{ md5($user->g_suite) }}"
                class="hidden absolute right-6 mt-2 w-48 bg-white rounded-lg shadow-lg border z-50"
            >

                <a
                    href="{{ route('admin.users.show', $user->g_suite) }}"
                    class="block px-4 py-2 hover:bg-gray-100"
                >
                    View Profile
                </a>

                <a href="#"
                    class="block px-4 py-2 hover:bg-gray-100">
                    Edit User
                </a>

                <a href="#"
                    class="block px-4 py-2 hover:bg-gray-100">
                    Change Status
                </a>

                <hr>

                <a href="#"
                    class="block px-4 py-2 text-red-600 hover:bg-red-50">
                    Delete User
                </a>

            </div>

        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <script>
        function toggleMenu(id){

            document.querySelectorAll("[id^='menu-']").forEach(menu=>{
                if(menu.id!="menu-"+id){
                    menu.classList.add("hidden");
                }
            });

            document
                .getElementById("menu-"+id)
                .classList.toggle("hidden");
        }

        window.addEventListener("click",function(e){

            if (!e.target.closest(".action-menu")) {

                document.querySelectorAll("[id^='menu-']").forEach(menu=>{
                    menu.classList.add("hidden");
                });

            }

        });
        </script>
@endsection