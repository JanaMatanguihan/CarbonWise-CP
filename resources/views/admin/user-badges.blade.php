@extends('layouts.admin')

@section('page-title', 'User Details')
@section('page-subtitle', 'User Management > User Details')

@section('content')

<div class="grid grid-cols-12 gap-6 mt-6">

    {{-- Left Sidebar --}}
    @include('admin.partials.user-sidebar')

    {{-- Right Side --}}
    <div class="col-span-8 flex">

        <div class="bg-white rounded-xl shadow flex flex-col w-full h-full">

            {{-- Tabs --}}
            @include('admin.partials.user-tabs')

            <div class="p-8">

                {{-- Header --}}
                <div class="flex justify-between items-start mb-8">

                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            Badges & Achievements
                        </h2>

                        <p class="text-sm text-gray-500 mt-1">
                            Track your streak and earn badges by building sustainable habits.
                        </p>
                    </div>

                </div>

                {{-- Statistics Cards --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- Current Streak --}}
                    <div class="bg-gradient-to-br from-orange-50 to-white border rounded-2xl p-6 min-h-[260px]">

                        <div class="flex items-center gap-4">

                            <div class="w-20 h-20 rounded-full bg-orange-100 flex items-center justify-center text-5xl">
                                🔥
                            </div>

                            <div>

                                <p class="text-gray-600 text-sm">
                                    Current Streak
                                </p>

                                <h2 class="text-4xl font-bold text-orange-500">
                                    {{ $currentStreak }}
                                    <span class="text-xl font-semibold">
                                        days
                                    </span>
                                </h2>

                                <p class="text-sm text-gray-500 mt-2">
                                    Keep it up! You're building a sustainable habit.
                                </p>

                                
                                {{-- Weekly Activity --}}
                                <div class="mt-8">

                                    <div class="grid grid-cols-7 gap-3">

                                        @foreach ($weekActivity as $day => $completed)

                                            <div class="flex flex-col items-center">

                                                <div
                                                    class="w-9 h-9 rounded-full border-2 flex items-center justify-center
                                                    {{ $completed
                                                        ? 'bg-green-500 border-green-500'
                                                        : 'bg-white border-gray-300' }}">
                                                </div>

                                                <span class="text-xs text-gray-500 mt-2">
                                                    {{ $day }}
                                                </span>

                                            </div>

                                        @endforeach

                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>

                    {{-- Green Points --}}
                    <div class="bg-gradient-to-br from-green-50 to-white border rounded-2xl p-6 min-h-[260px]">

                        <div class="flex items-center gap-4">

                            <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center text-5xl">
                                🌿
                            </div>

                            <div>

                                <p class="text-gray-600 text-sm">
                                    Green Points
                                </p>

                                <h2 class="text-4xl font-bold text-green-600">
                                    {{ number_format($greenPoints) }}
                                    <span class="text-xl font-semibold">
                                        pts
                                    </span>
                                </h2>

                                <p class="text-sm text-gray-500 mt-2">
                                    Great job reducing your carbon footprint!
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection