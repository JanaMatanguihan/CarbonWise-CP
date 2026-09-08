<div class="border-b">

    <div class="flex gap-10 px-8 py-5">

        {{-- Overview --}}
        <a href="{{ route('admin.users.show', $user->g_suite) }}"
            class="pb-4 border-b-[3px] transition-all duration-200
            {{ request()->routeIs('admin.users.show')
                ? 'border-green-600 text-green-700 font-semibold text-[16px]'
                : 'border-transparent text-gray-700 font-medium text-[16px] hover:text-green-600' }}">
            Overview
        </a>

        {{-- Carbon Records --}}
        <a href="{{ route('admin.users.records', $user->g_suite) }}"
            class="pb-4 border-b-[3px] transition-all duration-200
            {{ request()->routeIs('admin.users.records')
                ? 'border-green-600 text-green-700 font-semibold text-[16px]'
                : 'border-transparent text-gray-700 font-medium text-[16px] hover:text-green-600' }}">
            Carbon Records
        </a>

       {{-- Badges --}}
        <a href="{{ route('admin.users.badges', $user->g_suite) }}"
            class="pb-4 border-b-[3px] transition-all duration-200
            {{ request()->routeIs('admin.users.badges')
                ? 'border-green-600 text-green-700 font-semibold text-[16px]'
                : 'border-transparent text-gray-700 font-medium text-[16px] hover:text-green-600' }}">
            Badges
        </a>

    </div>

</div>