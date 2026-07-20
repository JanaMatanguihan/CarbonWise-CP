<!-- LEFT PROFILE CARD -->
    <div class="col-span-4">

        <div class="bg-white rounded-xl shadow p-8">

            <!-- Profile Picture -->
            <div class="flex justify-center">

                @if($user->profile_photo)
                    <img
                        src="{{ asset('storage/' . $user->profile_photo) }}"
                        class="w-40 h-40 rounded-full object-cover"
                    >
                @else
                    <img
                        src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name) }}&size=200&background=16a34a&color=ffffff"
                        class="w-40 h-40 rounded-full"
                    >
                @endif

            </div>

            <!-- Name -->
            <div class="mt-6 text-center">

                <h2 class="text-2xl font-bold">
                    {{ $user->full_name }}
                </h2>

                <p class="text-gray-500 mt-2">
                    {{ $user->g_suite }}
                </p>

                <span
                    class="inline-block mt-4 px-4 py-2 rounded-full bg-green-100 text-green-700 font-semibold"
                >
                    {{ ucfirst($user->role) }}
                </span>

            </div>

            <!-- Information -->
            <div class="mt-8 border rounded-xl p-5 space-y-4">

                <div class="flex justify-between">
                    <span class="text-gray-500">Department</span>
                    <span>{{ $user->department }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Campus</span>
                    <span>{{ $user->campus }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">SR Code</span>
                    <span>{{ $user->sr_code }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Year Level</span>
                    <span>{{ $user->year_level }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Joined</span>
                    <span>{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y') }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>

                    <span class="
                        font-semibold
                        {{ $user->status == 'Active'
                            ? 'text-green-600'
                            : 'text-red-600' }}
                    ">
                        {{ $user->status }}
                    </span>
                </div>

            </div>

            <!-- Edit Button -->
            <div class="mt-8">

                <a
                href="{{ route('admin.users.edit',$user->g_suite) }}"
                class="block w-full text-center border border-green-600 text-green-600 py-3 rounded-lg font-semibold hover:bg-green-50"
            >
                Edit User
            </a>

            </div>

        </div>

    </div>