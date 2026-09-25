@php
    // Find the user's profile photo

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

        if (
            isset($user->{$field}) &&
            !empty($user->{$field})
        ) {
            $photoPath = $user->{$field};
            break;
        }
    }

    // Build the photo URL

    $profileImage = null;

    if ($photoPath) {

        if (
            str_starts_with($photoPath, 'http://') ||
            str_starts_with($photoPath, 'https://')
        ) {

            $profileImage = $photoPath;

        } elseif (
            str_starts_with($photoPath, '/storage/')
        ) {

            $profileImage = asset(ltrim($photoPath, '/'));

        } elseif (
            str_starts_with($photoPath, 'storage/')
        ) {

            $profileImage = asset($photoPath);

        } else {

            $profileImage = asset('storage/' . ltrim($photoPath, '/'));

        }
    }

    // Generate initials if no photo exists

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
    .user-sidebar {
        width: 100%;
        font-family: 'Poppins', sans-serif;
    }

    .user-sidebar * {
        font-family: 'Poppins', sans-serif;
    }

    .user-sidebar-inner {
        padding: 28px;
        background: #ffffff;
    }

    .user-profile-photo {
        width: 128px;
        height: 128px;
        border-radius: 50%;
        object-fit: cover;
        display: block;
        border: 4px solid #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
    }

    .user-profile-initials {
        width: 128px;
        height: 128px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #2f9d68;
        color: #ffffff;
        font-size: 34px;
        line-height: 1;
        font-weight: 700;
        border: 4px solid #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
    }

    .user-email {
        margin-top: 18px;
        text-align: center;
        font-size: 13px;
        line-height: 1.5;
        color: #6b7280;
        font-weight: 500;
        word-break: break-word;
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
        font-size: 13px;
        line-height: 1.4;
        font-weight: 600;
    }

    .user-information {
        margin-top: 26px;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
    }

    .user-information-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 8px 0;
    }

    .user-information-label {
        font-size: 13px;
        line-height: 1.5;
        color: #6b7280;
        font-weight: 500;
    }

    .user-information-value {
        font-size: 13px;
        line-height: 1.5;
        color: #111827;
        font-weight: 600;
        text-align: right;
    }

    .user-information-status {
        font-size: 13px;
        color: #16a34a;
        font-weight: 600;
    }

    .user-edit-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 20px;
        padding: 11px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #15803d;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .user-edit-button:hover {
        background: #f0fdf4;
        border-color: #86efac;
    }
</style>


<div class="user-sidebar">

    <div class="user-sidebar-inner">

        {{-- Profile --}}
        <div class="flex flex-col items-center text-center">

            @if($profileImage)

                <img
                    src="{{ $profileImage }}"
                    alt="{{ $user->name }}"
                    class="user-profile-photo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >

                {{-- Fallback if image cannot load --}}
                <div
                    class="user-profile-initials"
                    style="display:none;"
                >
                    {{ $initials }}
                </div>

            @else

                <div class="user-profile-initials">
                    {{ $initials }}
                </div>

            @endif

            {{-- Email --}}
            <div class="user-email">
                {{ $user->email }}
            </div>

            {{-- Role --}}
            <span class="user-role">
                {{ ucfirst($user->role ?? 'User') }}
            </span>

        </div>


        {{-- User Information --}}
        <div class="user-information">

            <div class="user-information-row">

                <span class="user-information-label">
                    Department
                </span>

                <span class="user-information-value">
                    {{ $user->department ?: 'N/A' }}
                </span>

            </div>


            <div class="user-information-row">

                <span class="user-information-label">
                    Campus
                </span>

                <span class="user-information-value">
                    {{ $user->campus ?: 'N/A' }}
                </span>

            </div>


            <div class="user-information-row">

                <span class="user-information-label">
                    SR Code
                </span>

                <span class="user-information-value">
                    {{ $user->sr_code ?: 'N/A' }}
                </span>

            </div>


            <div class="user-information-row">

                <span class="user-information-label">
                    Year Level
                </span>

                <span class="user-information-value">
                    {{ $user->year_level ?: 'N/A' }}
                </span>

            </div>


            <div class="user-information-row">

                <span class="user-information-label">
                    Joined
                </span>

                <span class="user-information-value">
                    {{ $user->created_at ? $user->created_at->format('F d, Y') : 'N/A' }}
                </span>

            </div>


            <div class="user-information-row">

                <span class="user-information-label">
                    Status
                </span>

                <span class="user-information-status">
                    {{ ucfirst($user->status ?? 'Unknown') }}
                </span>

            </div>

        </div>


        {{-- Edit User --}}
        <a
            href="{{ route('admin.users.edit', $user->g_suite) }}"
            class="user-edit-button"
        >
            Edit User
        </a>

    </div>

</div>