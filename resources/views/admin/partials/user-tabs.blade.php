
<style>
    .user-detail-tabs {
        display: flex;
        align-items: center;
        gap: 28px;
        padding: 0 28px;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
    }

    .user-detail-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        padding: 20px 0 17px;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
        color: #4b5563;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        transition: color 0.2s ease, border-color 0.2s ease;
    }

    .user-detail-tab:hover {
        color: #15803d;
    }

    .user-detail-tab.active {
        color: #15803d;
        border-bottom-color: #22c55e;
        font-weight: 600;
    }

    html[data-theme="dark"] .user-detail-tabs {
        background: #121A16;
        border-bottom-color: #1B3A2B;
    }

    html[data-theme="dark"] .user-detail-tab {
        color: #9CA3AF;
    }

    html[data-theme="dark"] .user-detail-tab:hover {
        color: #76C893;
    }

    html[data-theme="dark"] .user-detail-tab.active {
        color: #76C893;
        border-bottom-color: #52B788;
    }

    @media (max-width: 640px) {
        .user-detail-tabs {
            gap: 22px;
            padding: 0 18px;
            overflow-x: auto;
        }
    }
</style>

<div class="user-detail-tabs">

    <a
        href="{{ route('admin.users.show', $user->g_suite) }}"
        class="user-detail-tab {{ request()->routeIs('admin.users.show') ? 'active' : '' }}"
        @if(request()->routeIs('admin.users.show')) aria-current="page" @endif
    >
        Overview
    </a>

    <a
        href="{{ route('admin.users.records', $user->g_suite) }}"
        class="user-detail-tab {{ request()->routeIs('admin.users.records') ? 'active' : '' }}"
        @if(request()->routeIs('admin.users.records')) aria-current="page" @endif
    >
        Carbon Records
    </a>

</div>
