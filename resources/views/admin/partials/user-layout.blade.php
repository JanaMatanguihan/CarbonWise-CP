@extends('layouts.admin')

@section('content')

<div class="p-8">

    <div class="grid grid-cols-12 gap-6">

        {{-- LEFT PROFILE CARD --}}
        <div class="col-span-4">
            @include('admin.partials.user-sidebar')
        </div>

        {{-- RIGHT CONTENT --}}
        <div class="col-span-8 bg-white rounded-2xl shadow">

            {{-- Tabs --}}
            @include('admin.partials.user-tabs')

            {{-- Dynamic Content --}}
            <div class="p-6">
                @yield('user-content')
            </div>

        </div>

    </div>

</div>

@endsection