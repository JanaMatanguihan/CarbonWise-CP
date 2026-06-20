<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🌿 CarbonWise Admin Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-green-100 border border-green-300 rounded-lg p-6 mb-6">
                <h3 class="text-2xl font-bold text-green-800">
                    Welcome, {{ Auth::user()->name }}!
                </h3>

                <p class="mt-2 text-green-700">
                    Role:
                    <strong>{{ ucfirst(Auth::user()->role) }}</strong>
                </p>

                <p class="mt-2 text-green-700">
                    You are successfully logged in to the CarbonWise system.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <div class="bg-white shadow rounded-lg p-5">
                    <h4 class="font-bold text-gray-700">👥 Total Users</h4>
                    <p class="text-3xl mt-2">--</p>
                </div>

                <div class="bg-white shadow rounded-lg p-5">
                    <h4 class="font-bold text-gray-700">🌱 Carbon Records</h4>
                    <p class="text-3xl mt-2">--</p>
                </div>

                <div class="bg-white shadow rounded-lg p-5">
                    <h4 class="font-bold text-gray-700">📊 Reports</h4>
                    <p class="text-3xl mt-2">--</p>
                </div>

                <div class="bg-white shadow rounded-lg p-5">
                    <h4 class="font-bold text-gray-700">🎯 Mitigation Plans</h4>
                    <p class="text-3xl mt-2">--</p>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>