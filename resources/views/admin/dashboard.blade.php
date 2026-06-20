@extends('layouts.admin')

@section('content')

<div class="container-fluid">

 <!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-5 mb-6">

    <!-- Total Users -->
    <div class="bg-white rounded-xl shadow-md p-5 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm">Total Users</p>
            <h2 class="text-3xl font-bold">{{ number_format($totalUsers) }}</h2>
            <p class="text-green-600 text-xs mt-2">↑ Active users</p>
        </div>

        <div class="bg-green-100 rounded-full p-3">
            <img src="{{ asset('icons/user.png') }}" class="w-10 h-10">
        </div>
    </div>

    <!-- Total Emissions -->
    <div class="bg-white rounded-xl shadow-md p-5 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm">Total Emissions</p>
            <h2 class="text-3xl font-bold">{{ number_format($totalEmissions,2) }}</h2>
            <p class="text-xs text-gray-500">kg CO₂e</p>
        </div>

        <div class="bg-blue-100 rounded-full p-3">
            <img src="{{ asset('icons/emissions.png') }}" class="w-10 h-10">
        </div>
    </div>

    <!-- Average -->
    <div class="bg-white rounded-xl shadow-md p-5 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm">Average / User</p>
            <h2 class="text-3xl font-bold">{{ number_format($averageEmission,2) }}</h2>
            <p class="text-xs text-gray-500">kg CO₂e</p>
        </div>

        <div class="bg-green-100 rounded-full p-3">
            <img src="{{ asset('icons/user.png') }}" class="w-10 h-10">
        </div>
    </div>

    <!-- Mitigation -->
    <div class="bg-white rounded-xl shadow-md p-5 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm">Mitigation Actions</p>
            <h2 class="text-3xl font-bold">{{ $mitigationCount }}</h2>
        </div>

        <div class="bg-green-100 rounded-full p-3">
            <img src="{{ asset('icons/mitigation.png') }}" class="w-10 h-10">
        </div>
    </div>

    <!-- Reports -->
    <div class="bg-white rounded-xl shadow-md p-5 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm">SDO Reports</p>
            <h2 class="text-3xl font-bold">{{ $reportCount }}</h2>
        </div>

        <div class="bg-purple-100 rounded-full p-3">
            <img src="{{ asset('icons/audit.png') }}" class="w-10 h-10">
        </div>
    </div>

</div>
        </div>

    </div>

</div>

@endsection