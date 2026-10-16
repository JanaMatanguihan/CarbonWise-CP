
@php
    $photoPath = null;

    $possiblePhotoFields = [
        'profile_photo',
        'profile_picture',
        'profile_photo_path',
        'avatar',
        'photo',
        'image',
    ];

    foreach ($possiblePhotoFields as $field) {
        if (isset($user->{$field}) && !empty($user->{$field})) {
            $photoPath = $user->{$field};
            break;
        }
    }

    $profileImage = null;

    if ($photoPath) {
        if (
            str_starts_with($photoPath, 'http://') ||
            str_starts_with($photoPath, 'https://')
        ) {
            $profileImage = $photoPath;
        } elseif (str_starts_with($photoPath, '/storage/')) {
            $profileImage = asset(ltrim($photoPath, '/'));
        } elseif (str_starts_with($photoPath, 'storage/')) {
            $profileImage = asset($photoPath);
        } else {
            $profileImage = asset('storage/' . ltrim($photoPath, '/'));
        }
    }

    $userName = trim($user->name ?? '');
    $nameParts = preg_split('/\s+/', $userName);

    if (count($nameParts) >= 2) {
        $initials = strtoupper(
            substr($nameParts[0], 0, 1) .
            substr($nameParts[count($nameParts) - 1], 0, 1)
        );
    } elseif (!empty($userName)) {
        $initials = strtoupper(substr($userName, 0, 2));
    } else {
        $initials = 'U';
    }
@endphp

<style>
    .user-sidebar,
    .user-sidebar * {
        font-family: 'Poppins', sans-serif;
        box-sizing: border-box;
    }

    .user-sidebar {
        width: 100%;
        color: #111827;
    }

    .user-sidebar-inner {
        width: 100%;
        padding: 28px 22px;
        background: #ffffff;
        border-radius: inherit;
    }

    .user-profile-photo,
    .user-profile-initials {
        width: 112px;
        height: 112px;
        flex-shrink: 0;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
    }

    .user-profile-photo {
        display: block;
        object-fit: cover;
    }

    .user-profile-initials {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #2f9d68;
        color: #ffffff;
        font-size: 34px;
        line-height: 1;
        font-weight: 700;
    }

    .user-email {
        max-width: 100%;
        margin-top: 18px;
        text-align: center;
        font-size: 12px;
        line-height: 1.5;
        color: #6b7280;
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    .user-role {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 14px;
        padding: 7px 20px;
        border-radius: 999px;
        background: #dcfce7;
        color: #15803d;
        font-size: 12px;
        line-height: 1.4;
        font-weight: 600;
    }

    .user-information {
        margin-top: 24px;
        padding: 14px 15px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
    }

    .user-information-row {
        display: grid;
        grid-template-columns: minmax(65px, 0.8fr) minmax(0, 1.2fr);
        align-items: center;
        gap: 10px;
        padding: 8px 0;
    }

    .user-information-label {
        min-width: 0;
        font-size: 12px;
        line-height: 1.5;
        color: #6b7280;
        font-weight: 500;
    }

    .user-information-value {
        min-width: 0;
        font-size: 12px;
        line-height: 1.5;
        color: #111827;
        font-weight: 600;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .user-information-status {
        font-size: 12px;
        color: #16a34a;
        font-weight: 600;
        text-align: right;
    }

    .user-edit-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 16px;
        padding: 11px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #15803d;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .user-edit-button:hover {
        background: #f0fdf4;
        border-color: #86efac;
    }

    /* Dark mode */
    html[data-theme="dark"] .user-sidebar,
    html[data-theme="dark"] .user-sidebar-inner {
        background: #121A16 !important;
        color: #F3F4F6 !important;
    }

    html[data-theme="dark"] .user-information {
        background: #121A16 !important;
        border-color: #1B3A2B !important;
    }

    html[data-theme="dark"] .user-information-label,
    html[data-theme="dark"] .user-email {
        color: #9CA3AF !important;
    }

    html[data-theme="dark"] .user-information-value {
        color: #F3F4F6 !important;
    }

    html[data-theme="dark"] .user-information-row {
        border-color: #1B3A2B;
    }

    html[data-theme="dark"] .user-profile-photo,
    html[data-theme="dark"] .user-profile-initials {
        border-color: #1B3A2B;
    }

    html[data-theme="dark"] .user-role {
        background: #163D2A;
        color: #76C893;
    }

    html[data-theme="dark"] .user-information-status {
        color: #76C893;
    }

    html[data-theme="dark"] .user-edit-button {
        background: #18241F;
        color: #76C893;
        border-color: #2D6A4F;
    }

    html[data-theme="dark"] .user-edit-button:hover {
        background: #162E24;
        color: #B7E4C7;
        border-color: #52B788;
    }

    @media (max-width: 640px) {
        .user-sidebar-inner {
            padding: 22px 18px;
        }
    }
</style>

<div class="user-sidebar">
    <div class="user-sidebar-inner">

        <div class="flex flex-col items-center text-center">
            @if($profileImage)
                <img
                    src="{{ $profileImage }}"
                    alt="{{ $user->name }}"
                    class="user-profile-photo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >

                <div class="user-profile-initials" style="display:none;">
                    {{ $initials }}
                </div>
            @else
                <div class="user-profile-initials">
                    {{ $initials }}
                </div>
            @endif

            <div class="user-email">
                {{ $user->email }}
            </div>

            <span class="user-role">
                {{ ucfirst($user->role ?? 'User') }}
            </span>
        </div>

        <div class="user-information">
            <div class="user-information-row">
                <span class="user-information-label">Department</span>
                <span class="user-information-value">
                    {{ $user->department ?: 'N/A' }}
                </span>
            </div>

            <div class="user-information-row">
                <span class="user-information-label">Campus</span>
                <span class="user-information-value">
                    {{ $user->campus ?: 'N/A' }}
                </span>
            </div>

            <div class="user-information-row">
                <span class="user-information-label">SR Code</span>
                <span class="user-information-value">
                    {{ $user->sr_code ?: 'N/A' }}
                </span>
            </div>

            <div class="user-information-row">
                <span class="user-information-label">Year Level</span>
                <span class="user-information-value">
                    {{ $user->year_level ?: 'N/A' }}
                </span>
            </div>

            <div class="user-information-row">
                <span class="user-information-label">Joined</span>
                <span class="user-information-value">
                    {{ $user->created_at ? $user->created_at->format('F d, Y') : 'N/A' }}
                </span>
            </div>

            <div class="user-information-row">
                <span class="user-information-label">Status</span>
                <span class="user-information-status">
                    {{ ucfirst($user->status ?? 'Unknown') }}
                </span>
            </div>
        </div>

        <a
            href="{{ route('admin.users.edit', $user->g_suite) }}"
            class="user-edit-button"
        >
            Edit User
        </a>

    </div>
</div>
