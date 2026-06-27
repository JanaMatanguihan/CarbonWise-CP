@extends('layouts.admin')

@section('page-title', 'Edit User')
@section('page-subtitle', 'User Management > Edit User')

@section('content')

<div class="max-w-5xl mx-auto mt-6">

    <div class="bg-white rounded-xl shadow">

        <div class="border-b px-8 py-6">
            <h2 class="text-2xl font-bold">
                Edit User
            </h2>

            <p class="text-gray-500 mt-2">
                Update the user's information.
            </p>
        </div>

        <form
            action="{{ route('admin.users.update', $user->g_suite) }}"
            method="POST"
            class="p-8"
        >

            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-6">

            <div>
    <label class="block mb-2 font-medium">Full Name</label>

    <input
        type="text"
        name="full_name"
        value="{{ old('full_name',$user->full_name) }}"
        class="w-full border rounded-lg px-4 py-3"
    >
</div>

<div>
    <label class="block mb-2 font-medium">G Suite</label>

    <input
    type="email"
    value="{{ $user->g_suite }}"
    class="w-full border rounded-lg px-4 py-3 bg-gray-100"
    readonly
>

<input
    type="hidden"
    name="g_suite"
    value="{{ $user->g_suite }}"
>
</div>

<div>
    <label class="block mb-2 font-medium">SR Code</label>

    <input
        type="text"
        name="sr_code"
        value="{{ old('sr_code',$user->sr_code) }}"
        class="w-full border rounded-lg px-4 py-3"
    >
</div>

<div>
    <label class="block mb-2 font-medium">Campus</label>

    <input
        type="text"
        name="campus"
        value="{{ old('campus',$user->campus) }}"
        class="w-full border rounded-lg px-4 py-3"
    >
</div>

<div>
    <label class="block mb-2 font-medium">Department</label>

    <input
        type="text"
        name="department"
        value="{{ old('department',$user->department) }}"
        class="w-full border rounded-lg px-4 py-3"
    >
</div>

<div>
    <label class="block mb-2 font-medium">Year Level</label>

    <input
        type="number"
        name="year_level"
        value="{{ old('year_level',$user->year_level) }}"
        class="w-full border rounded-lg px-4 py-3"
    >
</div>

<div>
    <label class="block mb-2 font-medium">Role</label>

    <select
        name="role"
        class="w-full border rounded-lg px-4 py-3"
    >
        <option value="student" {{ $user->role=='student'?'selected':'' }}>Student</option>
        <option value="faculty" {{ $user->role=='faculty'?'selected':'' }}>Faculty</option>
        <option value="staff" {{ $user->role=='staff'?'selected':'' }}>Staff</option>
    </select>
</div>

<div>
    <label class="block mb-2 font-medium">Status</label>

    <select
        name="status"
        class="w-full border rounded-lg px-4 py-3"
    >
        <option value="Active" {{ $user->status=='Active'?'selected':'' }}>Active</option>
        <option value="Inactive" {{ $user->status=='Inactive'?'selected':'' }}>Inactive</option>
    </select>
</div>
</div>

<div class="flex justify-end gap-4 mt-8">

    <a
        href="{{ route('admin.users.show',$user->g_suite) }}"
        class="px-6 py-3 rounded-lg border"
    >
        Cancel
    </a>

    <button
        type="submit"
        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700"
    >
        Save Changes
    </button>

</div>

</form>

</div>

</div>

@endsection