@extends('layouts.admin')

@section('page-title', 'User Activity Logs')
@section('page-subtitle', 'User Management > User Activity Logs')

@section('content')

<div class="grid grid-cols-12 gap-6 mt-6">

    {{-- Shared Sidebar --}}
    @include('admin.partials.user-sidebar')

    {{-- Right Side --}}
    <div class="col-span-8">

        {{-- Shared Tabs --}}
        @include('admin.partials.user-tabs')

        <div class="bg-white rounded-xl shadow h-[600px] p-8">

            <h2 class="text-2xl font-bold mb-2">
                Activity Logs
            </h2>

            <p class="text-gray-500">
                Activity Logs table will go here.
            </p>

        </div>

    </div>

</div>

@endsection