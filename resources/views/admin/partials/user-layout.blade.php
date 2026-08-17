@extends('layouts.admin')

@section('content')

<div class="container-fluid py-4">

    <div class="row">

        {{-- Left Sidebar --}}
        <div class="col-lg-4 mb-4">

            @include('admin.partials.user-sidebar')

        </div>

        {{-- Right Content --}}
        <div class="col-lg-8">

            <div class="card shadow-sm border-0 rounded-4">

                @include('admin.partials.user-tabs')

                <div class="card-body p-4">

                    @yield('user-content')

                </div>

            </div>

        </div>

    </div>

</div>

@endsection