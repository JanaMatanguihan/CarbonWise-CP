@extends('layouts.admin')

@section('page-title', 'User Management')
@section('page-subtitle', 'Manage and monitor all system users')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    /* =========================================
       Main Page
    ========================================== */

    .users-page {
        font-family: 'Poppins', sans-serif;
        color: #111111;
    }

    .users-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
        overflow: visible;
    }

    /* =========================================
       Toolbar
    ========================================== */

    .users-toolbar {
        min-height: 76px;
        border-bottom: 1px solid #e5e7eb;
    }

    .users-search-wrapper {
        position: relative;
        width: 205px;
        flex-shrink: 0;
    }

    .users-search {
        width: 100%;
        height: 38px;
        padding: 0 12px 0 38px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        color: #111111;
        outline: none;
    }

    .users-search::placeholder {
        color: #9ca3af;
    }

    .users-search:focus {
        border-color: #2f7d57;
        box-shadow: 0 0 0 1px rgba(47, 125, 87, 0.15);
    }

    .users-filter {
        height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        color: #374151;
        padding: 0 30px 0 12px;
        outline: none;
    }

    .users-filter:focus {
        border-color: #2f7d57;
        box-shadow: 0 0 0 1px rgba(47, 125, 87, 0.15);
    }

    .users-add-button {
        height: 38px;
        padding: 0 17px;
        background: #2f9d68;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        cursor: pointer;
        transition: background 0.2s ease;
        text-decoration: none;
    }

    .users-add-button:hover {
        background: #287f55;
        color: #ffffff;
    }

    /* =========================================
       Table
    ========================================== */

    .users-table-wrapper {
    width: 100%;
    overflow: visible;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .users-table thead th {
        height: 52px;
        padding: 0 18px;
        border-bottom: 1px solid #d1d5db;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 16px;
        font-weight: 600;
        color: #111111;
        text-align: left;
        white-space: nowrap;
    }

    .users-table tbody tr {
        height: 66px;
        border-bottom: 1px solid #eeeeee;
        transition: background 0.15s ease;
    }

    .users-table tbody tr:last-child {
        border-bottom: none;
    }

    .users-table tbody tr:hover {
        background: #fafafa;
    }

    .users-table tbody td {
        padding: 9px 18px;
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 17px;
        color: #111111;
        vertical-align: middle;
        position: relative;
    }

    /* =========================================
       User Profile
    ========================================== */

    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e5e7eb;
    }

    .user-initials {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #2f9d68;
        color: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-size: 13px;
        line-height: 18px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .user-info {
        min-width: 0;
    }

    .user-name {
        font-family: 'Poppins', sans-serif;
        font-size: 12px;
        line-height: 18px;
        font-weight: 600;
        color: #111111;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-email {
        margin-top: 2px;
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 14px;
        font-weight: 400;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* =========================================
       Table Text
    ========================================== */

    .user-role {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        line-height: 17px;
        font-weight: 500;
        color: #111111;
        text-transform: capitalize;
    }

    .user-department {
        display: block;
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 16px;
        font-weight: 400;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-joined {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 16px;
        font-weight: 400;
        color: #374151;
        white-space: nowrap;
    }

    /* =========================================
       Status
    ========================================== */

    .user-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 58px;
        height: 25px;
        padding: 0 11px;
        border-radius: 2px;
        font-family: 'Poppins', sans-serif;
        font-size: 9px;
        line-height: 14px;
        font-weight: 500;
    }

    .user-status-active {
        background: #86efac;
        color: #166534;
    }

    .user-status-inactive {
        background: #fca5a5;
        color: #b91c1c;
    }

    .user-status-pending {
        background: #fde68a;
        color: #92400e;
    }

    /* =========================================
       Action Menu
    ========================================== */

    .actions-cell {
        position: relative !important;
        text-align: center;
        overflow: visible !important;
        vertical-align: middle;
    }

    .action-menu {
        position: relative;
        width: 100%;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .action-button {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        border-radius: 6px;
        background: transparent;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .action-button:hover {
        background: #f3f4f6;
        border-color: #e5e7eb;
        color: #111111;
    }

    .action-button svg {
        width: 18px;
        height: 18px;
    }

    .action-menu-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 50%;
        right: auto;
        transform: translateX(-50%);

        width: 165px;
        min-width: 165px;

        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 7px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.14);

        overflow: hidden;
        z-index: 9999;
    }

    .action-menu-dropdown a,
    .action-menu-dropdown button {
        width: 100%;
        min-height: 42px;
        display: flex;
        align-items: center;
        padding: 0 16px;
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        text-align: left;
        text-decoration: none;
        box-sizing: border-box;
    }

    .action-menu-dropdown a {
        color: #374151;
    }

    .action-menu-dropdown a:hover {
        background: #f9fafb;
    }

    .action-menu-dropdown button {
        border: none;
        background: #ffffff;
        color: #dc2626;
        cursor: pointer;
    }

    .action-menu-dropdown button:hover {
        background: #fef2f2;
    }

    /* =========================================
       Pagination
    ========================================== */

    .users-pagination {
        min-height: 62px;
        border-top: 1px solid #e5e7eb;
    }

    .users-pagination-text {
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        line-height: 15px;
        color: #9ca3af;
        white-space: nowrap;
    }

    .users-pagination-text strong {
        color: #6b7280;
        font-weight: 500;
    }

    .pagination-list {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .pagination-button {
        min-width: 30px;
        height: 30px;
        padding: 0 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background: #ffffff;
        color: #374151;
        font-family: 'Poppins', sans-serif;
        font-size: 9px;
        line-height: 14px;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .pagination-button:hover {
        background: #f3f4f6;
        color: #111111;
    }

    .pagination-current {
        background: #f3f4f6;
        color: #111111;
        font-weight: 600;
    }

    .pagination-disabled {
        color: #d1d5db;
        background: #ffffff;
        cursor: not-allowed;
    }

    .pagination-dots {
        min-width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 9px;
    }

    /* =========================================
       Responsive
    ========================================== */

    @media (max-width: 1200px) {

        .users-table {
            min-width: 1050px;
        }

        .users-table-wrapper {
            overflow-x: auto;
        }
    }

    @media (max-width: 900px) {

        .users-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .users-search-wrapper {
            width: 100%;
        }

        .users-filter-group {
            width: 100%;
            flex-wrap: wrap;
        }

        .users-filter {
            flex: 1;
            min-width: 130px;
        }

        .users-add-button {
            flex-shrink: 0;
        }

        .users-pagination {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding-top: 14px;
            padding-bottom: 14px;
        }
    }

    /* =========================================
       CarbonWise theme support
       Follows the shared admin layout's data-theme
    ========================================== */

    .users-page {
        background: var(--page-background, #f1f1ee) !important;
        color: var(--text-primary, #111111);
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .users-card {
        background: var(--card-background, #ffffff);
        color: var(--text-primary, #111111);
        border-color: var(--border-color, #e5e7eb);
        max-width: 100%;
        min-width: 0;
    }

    .users-toolbar,
    .users-pagination {
        border-color: var(--border-color, #e5e7eb);
    }

    .users-search,
    .users-filter {
        background: var(--input-bg, #ffffff);
        color: var(--text-primary, #111111);
        border-color: var(--input-border, #d1d5db);
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .users-filter option {
        background: var(--card-background, #ffffff);
        color: var(--text-primary, #111111);
    }

    .users-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    .users-table {
        min-width: 900px;
    }

    .users-table thead th {
        color: var(--text-primary, #111111);
        border-color: var(--border-color, #d1d5db);
    }

    .users-table tbody tr {
        border-color: var(--border-color, #eeeeee);
    }

    .users-table tbody tr:hover {
        background: var(--bg-sidebar-hover, #fafafa);
    }

    .users-table tbody td,
    .user-name,
    .user-role {
        color: var(--text-primary, #111111);
    }

    .user-email,
    .user-department,
    .user-joined {
        color: var(--text-muted, #6b7280);
    }

    .action-button {
        color: var(--text-secondary, #4b5563);
    }

    .action-button:hover {
        background: var(--bg-sidebar-hover, #f3f4f6);
        border-color: var(--border-color, #e5e7eb);
        color: var(--text-primary, #111111);
    }

    .action-menu-dropdown {
        background: var(--card-background, #ffffff);
        border-color: var(--border-color, #e5e7eb);
    }

    .action-menu-dropdown a {
        color: var(--text-primary, #374151);
    }

    .action-menu-dropdown a:hover {
        background: var(--bg-sidebar-hover, #f9fafb);
    }

    .action-menu-dropdown button {
        background: var(--card-background, #ffffff);
    }

    .action-menu-dropdown .border-t {
        border-color: var(--border-color, #e5e7eb);
    }

    .users-pagination-text {
        color: var(--text-muted, #9ca3af);
    }

    .users-pagination-text strong {
        color: var(--text-secondary, #6b7280);
    }

    .pagination-button {
        background: var(--input-bg, #ffffff);
        border-color: var(--input-border, #d1d5db);
        color: var(--text-secondary, #374151);
    }

    .pagination-button:hover,
    .pagination-current {
        background: var(--bg-sidebar-hover, #f3f4f6);
        color: var(--text-primary, #111111);
    }

    .pagination-disabled {
        background: var(--card-background, #ffffff);
        color: var(--text-muted, #d1d5db);
    }

    [data-theme="dark"] .users-page {
        --page-background: #0A0F0D;
        --card-background: #121A16;
        --text-primary: #F3F4F6;
        --text-secondary: #9CA3AF;
        --text-muted: #9CA3AF;
        --border-color: #1B3A2B;
        --input-bg: #18241F;
        --input-border: #2D6A4F;
        --bg-sidebar-hover: #162E24;
        background: #0A0F0D !important;
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .users-card,
    [data-theme="dark"] .action-menu-dropdown {
        background: #121A16 !important;
        border-color: #1B3A2B !important;
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .users-toolbar,
    [data-theme="dark"] .users-pagination,
    [data-theme="dark"] .users-table thead th,
    [data-theme="dark"] .users-table tbody tr {
        border-color: #1B3A2B !important;
    }

    [data-theme="dark"] .users-search,
    [data-theme="dark"] .users-filter,
    [data-theme="dark"] .pagination-button {
        background: #18241F !important;
        color: #F3F4F6 !important;
        border-color: #2D6A4F !important;
    }

    [data-theme="dark"] .users-table thead th,
    [data-theme="dark"] .users-table tbody td,
    [data-theme="dark"] .user-name,
    [data-theme="dark"] .user-role,
    [data-theme="dark"] .action-menu-dropdown a {
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .user-email,
    [data-theme="dark"] .user-department,
    [data-theme="dark"] .user-joined,
    [data-theme="dark"] .users-pagination-text,
    [data-theme="dark"] .users-pagination-text strong {
        color: #9CA3AF !important;
    }

    [data-theme="dark"] .users-table tbody tr:hover,
    [data-theme="dark"] .action-button:hover,
    [data-theme="dark"] .action-menu-dropdown a:hover,
    [data-theme="dark"] .pagination-button:hover,
    [data-theme="dark"] .pagination-current {
        background: #162E24 !important;
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .action-button {
        color: #9CA3AF;
    }

    [data-theme="dark"] .action-menu-dropdown button {
        background: #121A16;
        color: #f87171;
    }

    [data-theme="dark"] .action-menu-dropdown .border-t {
        border-color: #1B3A2B !important;
    }

    /* Keep toolbar usable at browser zoom and narrow widths. */
    .users-toolbar {
        flex-wrap: wrap;
    }

    .users-filter-group {
        display: flex;
        flex-wrap: wrap;
        min-width: 0;
        max-width: 100%;
    }

    @media (max-width: 1200px) {
        .users-table {
            min-width: 900px;
        }

        .users-table-wrapper {
            overflow-x: auto;
        }
    }

    @media (max-width: 900px) {
        .users-page {
            padding: 1rem !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
        }

        .users-toolbar {
            align-items: stretch;
            gap: 12px;
            padding-left: 16px;
            padding-right: 16px;
        }

        .users-filter-group {
            gap: 8px;
        }

        .users-filter {
            flex: 1 1 130px;
            width: auto !important;
        }

        .users-add-button {
            flex: 1 1 100%;
            min-height: 38px;
        }

        .users-pagination {
            align-items: flex-start;
            gap: 10px;
            padding-left: 16px;
            padding-right: 16px;
            overflow-x: auto;
        }

        .pagination-list {
            flex-wrap: wrap;
        }
    }



    /* =========================================
       Readability improvements
       Keep table text readable at normal zoom
       and allow wrapping instead of overflow.
    ========================================== */
    .users-page {
        font-size: 14px;
    }

    .users-table thead th {
        font-size: 14px !important;
        line-height: 1.5;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .users-table tbody td {
        font-size: 14px !important;
        line-height: 1.6;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .user-name {
        font-size: 15px !important;
        line-height: 1.5;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .user-email,
    .user-department,
    .user-joined {
        font-size: 13px !important;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .user-role {
        font-size: 13px !important;
        line-height: 1.5;
    }

    .users-search,
    .users-filter {
        font-size: 14px !important;
    }

    .users-add-button {
        font-size: 13px !important;
    }

    .users-pagination-text,
    .pagination-button,
    .pagination-dots {
        font-size: 13px !important;
        line-height: 1.5;
    }

    .user-status {
        font-size: 12px !important;
        min-width: 64px;
        padding: 5px 8px;
        line-height: 1.4;
    }

    .users-search,
    .users-filter,
    .users-add-button,
    .pagination-button {
        min-height: 36px;
    }

    .users-table {
        table-layout: auto;
    }

    .users-table th,
    .users-table td {
        min-width: 90px;
    }

    .users-table th:first-child,
    .users-table td:first-child {
        min-width: 250px;
    }

    @media (max-width: 900px) {
        .users-toolbar {
            gap: 12px;
            padding: 16px !important;
        }

        .users-search-wrapper {
            width: min(100%, 280px);
            flex: 1 1 220px;
        }

        .users-filter-group {
            gap: 8px;
            flex: 1 1 100%;
        }

        .users-filter {
            flex: 1 1 140px;
        }

        .users-add-button {
            min-height: 38px;
        }
    }

</style>


<div class="users-page bg-[#f1f1ee] min-h-screen p-5 md:p-6 space-y-5 -mx-6 -mt-6 pb-10 w-[calc(100%_+_3rem)]">

    <!-- =========================================
         USER MANAGEMENT CARD
    ========================================== -->

    <div class="users-card">

        <!-- =========================================
             SEARCH AND FILTER TOOLBAR
        ========================================== -->

        <form
            method="GET"
            id="filterForm"
            class="users-toolbar flex items-center justify-between gap-6 px-6 py-4"
        >

            <!-- Search -->

            <div class="users-search-wrapper">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                    />
                </svg>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search users..."
                    class="users-search"
                    onkeydown="if(event.key === 'Enter'){document.getElementById('filterForm').submit();}"
                >

            </div>


            <!-- Filters -->

            <div class="users-filter-group flex items-center gap-3">

                <!-- Role -->

                <select
                    name="role"
                    class="users-filter w-[130px]"
                    onchange="document.getElementById('filterForm').submit();"
                >
                    <option value="">
                        All Roles
                    </option>

                    <option
                        value="faculty"
                        {{ request('role') == 'faculty' ? 'selected' : '' }}
                    >
                        Faculty
                    </option>

                    <option
                        value="student"
                        {{ request('role') == 'student' ? 'selected' : '' }}
                    >
                        Student
                    </option>

                    <option
                        value="staff"
                        {{ request('role') == 'staff' ? 'selected' : '' }}
                    >
                        Staff
                    </option>

                    <option
                        value="admin"
                        {{ request('role') == 'admin' ? 'selected' : '' }}
                    >
                        Admin
                    </option>
                </select>


                <!-- Department -->

                <select
                    name="department"
                    class="users-filter w-[155px]"
                    onchange="document.getElementById('filterForm').submit();"
                >
                    <option value="">
                        All Departments
                    </option>

                    @foreach($departments as $department)

                        <option
                            value="{{ $department }}"
                            {{ request('department') == $department ? 'selected' : '' }}
                        >
                            {{ $department }}
                        </option>

                    @endforeach

                </select>


                <!-- Status -->

                <select
                    name="status"
                    class="users-filter w-[125px]"
                    onchange="document.getElementById('filterForm').submit();"
                >
                    <option value="">
                        All Status
                    </option>

                    <option
                        value="Active"
                        {{ request('status') == 'Active' ? 'selected' : '' }}
                    >
                        Active
                    </option>

                    <option
                        value="Inactive"
                        {{ request('status') == 'Inactive' ? 'selected' : '' }}
                    >
                        Inactive
                    </option>

                    <option
                        value="Pending"
                        {{ request('status') == 'Pending' ? 'selected' : '' }}
                    >
                        Pending
                    </option>
                </select>


                <!-- Add User -->

                <a
                    href="{{ route('admin.users.create') }}"
                    class="users-add-button"
                >
                    <span class="mr-1 text-base leading-none">
                        +
                    </span>

                    Add User
                </a>

            </div>

        </form>


        <!-- =========================================
             USERS TABLE
        ========================================== -->

        <div class="users-table-wrapper">

            <table class="users-table">

                <thead>

                    <tr>

                        <th style="width: 29%;">
                            User
                        </th>

                        <th style="width: 13%;">
                            Role
                        </th>

                        <th style="width: 28%;">
                            Department/College
                        </th>

                        <th style="width: 10%;">
                            Status
                        </th>

                        <th style="width: 12%;">
                            Joined
                        </th>

                        <th
                            style="width: 8%;"
                            class="text-center"
                        >
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach ($users as $index => $user)

                        @php

                            // Get the user's display name.

                            $displayName =
                                $user->full_name
                                ?? $user->name
                                ?? 'User';


                            // Create initials when no profile picture exists.

                            $nameParts = preg_split(
                                '/\s+/',
                                trim($displayName)
                            );


                            $firstInitial =
                                isset($nameParts[0])
                                ? substr(
                                    $nameParts[0],
                                    0,
                                    1
                                )
                                : 'U';


                            $lastInitial =
                                count($nameParts) > 1
                                ? substr(
                                    $nameParts[count($nameParts) - 1],
                                    0,
                                    1
                                )
                                : '';


                            $initials = strtoupper(
                                $firstInitial . $lastInitial
                            );


                            // Support both possible profile picture columns.

                            $profilePath =
                                $user->profile_photo
                                ?? $user->profile_picture
                                ?? null;


                            // Build the profile picture URL.

                            if ($profilePath) {

                                if (
                                    str_starts_with(
                                        $profilePath,
                                        'http://'
                                    )
                                    ||
                                    str_starts_with(
                                        $profilePath,
                                        'https://'
                                    )
                                ) {

                                    $profileUrl = $profilePath;

                                } else {

                                    $profileUrl = asset(
                                        'storage/' .
                                        ltrim(
                                            $profilePath,
                                            '/'
                                        )
                                    );
                                }

                            } else {

                                $profileUrl = null;

                            }

                        @endphp


                        <tr>

                            <!-- User -->

                            <td>

                                <div class="user-profile">

                                    @if($profileUrl)

                                        <img
                                            src="{{ $profileUrl }}"
                                            alt="{{ $displayName }}"
                                            class="user-avatar"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        >

                                        <div
                                            class="user-initials"
                                            style="display: none;"
                                        >
                                            {{ $initials }}
                                        </div>

                                    @else

                                        <div class="user-initials">
                                            {{ $initials }}
                                        </div>

                                    @endif


                                    <!-- User Details -->

                                    <div class="user-info">

                                        <div class="user-name">
                                            {{ $displayName }}
                                        </div>

                                        <div class="user-email">
                                            {{ $user->g_suite ?? $user->email ?? 'N/A' }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            <!-- Role -->

                            <td>

                                <span class="user-role">
                                    {{ $user->role ?? 'N/A' }}
                                </span>

                            </td>


                            <!-- Department -->

                            <td>

                                <span
                                    class="user-department"
                                    title="{{ $user->department ?? 'N/A' }}"
                                >
                                    {{ $user->department ?? 'N/A' }}
                                </span>

                            </td>


                            <!-- Status -->

                            <td>

                                @if($user->status == 'Active')

                                    <span class="user-status user-status-active">
                                        Active
                                    </span>

                                @elseif($user->status == 'Pending')

                                    <span class="user-status user-status-pending">
                                        Pending
                                    </span>

                                @else

                                    <span class="user-status user-status-inactive">
                                        Inactive
                                    </span>

                                @endif

                            </td>


                            <!-- Joined -->

                            <td>

                                <span class="user-joined">

                                    {{ $user->created_at
                                        ? \Carbon\Carbon::parse($user->created_at)->format('F d, Y')
                                        : 'N/A'
                                    }}

                                </span>

                            </td>


                            <!-- Actions -->

                            <td class="actions-cell">

                                <div class="action-menu">

                                    <button
                                        type="button"
                                        onclick="toggleMenu('{{ md5($user->g_suite) }}')"
                                        class="action-button"
                                        aria-label="User actions"
                                    >

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                        >

                                            <circle
                                                cx="12"
                                                cy="5"
                                                r="1.5"
                                            />

                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="1.5"
                                            />

                                            <circle
                                                cx="12"
                                                cy="19"
                                                r="1.5"
                                            />

                                        </svg>

                                    </button>


                                    <!-- Action Dropdown -->

                                    <div
                                        id="menu-{{ md5($user->g_suite) }}"
                                        class="hidden action-menu-dropdown
                                        {{ $index >= $users->count() - 3
                                            ? 'bottom-full mb-1'
                                            : 'top-full mt-1'
                                        }}"
                                    >

                                        <!-- View Profile -->

                                        <a
                                            href="{{ route('admin.users.show', $user->g_suite) }}"
                                        >
                                            View Profile
                                        </a>


                                        <!-- Divider -->

                                        <div class="border-t border-gray-100"></div>


                                        <!-- Delete User -->

                                        <form
                                            action="{{ route('admin.users.destroy', $user->g_suite) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this user?');"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                            >
                                                Delete User
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        <!-- =========================================
             PAGINATION
        ========================================== -->

        <div class="users-pagination flex items-center justify-between px-6 py-4">

            <!-- Result Count -->

            <div class="users-pagination-text">

                Showing

                <strong>
                    {{ $users->firstItem() ?? 0 }}
                </strong>

                to

                <strong>
                    {{ $users->lastItem() ?? 0 }}
                </strong>

                of

                <strong>
                    {{ $users->total() }}
                </strong>

                users

            </div>


            <!-- Custom Pagination -->

            @if($users->hasPages())

                <div class="pagination-list">

                    <!-- Previous -->

                    @if($users->onFirstPage())

                        <span class="pagination-button pagination-disabled">
                            ‹
                        </span>

                    @else

                        <a
                            href="{{ $users->previousPageUrl() }}"
                            class="pagination-button"
                        >
                            ‹
                        </a>

                    @endif


                    @php

                        $currentPage = $users->currentPage();
                        $lastPage = $users->lastPage();
                        $pages = [];

                        if ($lastPage <= 7) {

                            for (
                                $page = 1;
                                $page <= $lastPage;
                                $page++
                            ) {

                                $pages[] = $page;

                            }

                        } else {

                            $pages[] = 1;

                            if ($currentPage > 4) {
                                $pages[] = '...';
                            }

                            $start = max(
                                2,
                                $currentPage - 1
                            );

                            $end = min(
                                $lastPage - 1,
                                $currentPage + 1
                            );

                            for (
                                $page = $start;
                                $page <= $end;
                                $page++
                            ) {

                                $pages[] = $page;

                            }

                            if ($currentPage < $lastPage - 3) {
                                $pages[] = '...';
                            }

                            $pages[] = $lastPage;

                        }

                    @endphp


                    @foreach($pages as $page)

                        @if($page === '...')

                            <span class="pagination-dots">
                                ...
                            </span>

                        @elseif($page == $currentPage)

                            <span class="pagination-button pagination-current">
                                {{ $page }}
                            </span>

                        @else

                            <a
                                href="{{ $users->url($page) }}"
                                class="pagination-button"
                            >
                                {{ $page }}
                            </a>

                        @endif

                    @endforeach


                    <!-- Next -->

                    @if($users->hasMorePages())

                        <a
                            href="{{ $users->nextPageUrl() }}"
                            class="pagination-button"
                        >
                            ›
                        </a>

                    @else

                        <span class="pagination-button pagination-disabled">
                            ›
                        </span>

                    @endif

                </div>

            @endif

        </div>

    </div>

</div>


<!-- =========================================
     ACTION MENU SCRIPT
========================================== -->

<script>

function toggleMenu(id)
{
    document
        .querySelectorAll("[id^='menu-']")
        .forEach(function (menu) {

            if (menu.id !== "menu-" + id) {
                menu.classList.add("hidden");
            }

        });


    const selectedMenu =
        document.getElementById(
            "menu-" + id
        );


    if (selectedMenu) {

        selectedMenu.classList.toggle("hidden");

    }
}


window.addEventListener(
    "click",
    function (event) {

        if (!event.target.closest(".action-menu")) {

            document
                .querySelectorAll("[id^='menu-']")
                .forEach(function (menu) {

                    menu.classList.add("hidden");

                });

        }

    }
);

</script>

@endsection