
@extends('layouts.admin')

@section('page-title', 'Mitigation Strategies')
@section('page-subtitle', 'Manage and promote mitigation actions')

@section('content')
<div class="mitigation-page space-y-6">

    {{-- Page heading --}}
    <div class="mitigation-heading">
        <div>
            <h2 class="mitigation-page-title">Mitigation Actions</h2>
            <p class="mitigation-page-subtitle">
                Track and manage carbon reduction strategies.
            </p>
        </div>

        <button type="button" id="openAddModal" class="add-strategy-button">
            <span aria-hidden="true">+</span>
            Add Strategy
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div role="status" class="mitigation-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div role="alert" class="mitigation-error">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
        <div role="alert" class="mitigation-error">
            <p class="mb-2 font-semibold">Please correct the following:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main strategies card --}}
    <section class="mitigation-card">

        {{-- Filters --}}
        <div class="mitigation-tabs">
            <a href="{{ route('admin.mitigation') }}"
               @if(!request('status')) aria-current="page" @endif
               class="mitigation-tab {{ !request('status') ? 'is-active' : '' }}">
                All Strategies
            </a>

            <a href="{{ route('admin.mitigation', ['status' => 'in_progress']) }}"
               @if(request('status') === 'in_progress') aria-current="page" @endif
               class="mitigation-tab {{ request('status') === 'in_progress' ? 'is-active' : '' }}">
                Active
            </a>

            <a href="{{ route('admin.mitigation', ['status' => 'completed']) }}"
               @if(request('status') === 'completed') aria-current="page" @endif
               class="mitigation-tab {{ request('status') === 'completed' ? 'is-active' : '' }}">
                Completed
            </a>
        </div>

        {{-- Strategies table --}}
        <div class="mitigation-table-wrap">
            <table class="mitigation-table">
                <thead>
                    <tr>
                        <th scope="col">Strategy</th>
                        <th scope="col">Created By</th>
                        <th scope="col">Carbon Reduced</th>
                        <th scope="col">Status</th>
                        <th scope="col">Completed</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($strategies as $strategy)
                        @php
                            $status = $strategy->status ?? 'pending';

                            $statusClasses = match ($status) {
                                'completed' => 'status-completed',
                                'in_progress' => 'status-active',
                                default => 'status-pending',
                            };
                        @endphp

                        <tr>
                            <td class="strategy-cell">
                                <p class="strategy-title">
                                    {{ $strategy->title }}
                                </p>

                                @if($strategy->description)
                                    <p class="strategy-description">
                                        {{ $strategy->description }}
                                    </p>
                                @endif
                            </td>

                            <td>
                                @if($strategy->user)
                                    <p class="creator-name">
                                        {{ $strategy->user->name }}
                                    </p>
                                    <p class="creator-email">
                                        {{ $strategy->user->email }}
                                    </p>
                                @else
                                    <span class="muted-text">Unknown user</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap">
                                <span class="carbon-value">
                                    {{ number_format((float) $strategy->carbon_reduced, 2) }}
                                </span>
                                <span class="carbon-unit">kg CO₂e</span>
                            </td>

                            <td>
                                <span class="status-badge {{ $statusClasses }}">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap">
                                @if($strategy->completed_at)
                                    <span class="completed-date">
                                        {{ \Illuminate\Support\Carbon::parse($strategy->completed_at)->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="muted-text">—</span>
                                @endif
                            </td>

                            <td class="action-cell">
                                <button
                                    type="button"
                                    class="action-menu-trigger"
                                    aria-label="Actions for {{ $strategy->title }}"
                                    aria-expanded="false"
                                    aria-haspopup="true"
                                    data-menu-id="strategy-menu-{{ $strategy->id }}">
                                    <span aria-hidden="true">⋮</span>
                                </button>

                                {{-- Floating action menu --}}
                                <div id="strategy-menu-{{ $strategy->id }}"
                                     class="strategy-action-menu"
                                     hidden>
                                    <form method="POST"
                                          action="{{ route('admin.mitigation.destroy', $strategy->id) }}"
                                          onsubmit="return confirm('Delete this mitigation strategy? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="delete-strategy-button">
                                            <span aria-hidden="true">🗑</span>
                                            Delete strategy
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cell">
                                <div class="empty-state">
                                    <div class="empty-icon" aria-hidden="true">🌱</div>

                                    <p class="empty-title">
                                        No mitigation strategies found
                                    </p>

                                    <p class="empty-description">
                                        Add a strategy or select another filter to
                                        see available mitigation actions.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mitigation-pagination">
            <p class="pagination-summary">
                @if($strategies->total() > 0)
                    Showing
                    <strong>{{ $strategies->firstItem() }}–{{ $strategies->lastItem() }}</strong>
                    of
                    <strong>{{ $strategies->total() }}</strong>
                    strategies
                @else
                    0 strategies
                @endif
            </p>

            @if($strategies->hasPages())
                <nav aria-label="Mitigation strategies pagination"
                     class="pagination-controls">

                    @if($strategies->onFirstPage())
                        <span class="pagination-disabled">Previous</span>
                    @else
                        <a href="{{ $strategies->previousPageUrl() }}"
                           class="pagination-link">Previous</a>
                    @endif

                    @foreach($strategies->getUrlRange(1, $strategies->lastPage()) as $page => $url)
                        @if($page === $strategies->currentPage())
                            <span aria-current="page" class="pagination-current">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="pagination-link"
                               aria-label="Go to page {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if($strategies->hasMorePages())
                        <a href="{{ $strategies->nextPageUrl() }}"
                           class="pagination-link">Next</a>
                    @else
                        <span class="pagination-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
</div>

{{-- Add Strategy Modal --}}
<div id="addModal"
     class="mitigation-modal"
     aria-hidden="true"
     hidden
     data-open-on-load="{{ $errors->any() ? 'true' : 'false' }}">

    <div class="mitigation-modal-backdrop" data-close-modal></div>

    <section class="mitigation-modal-panel"
             role="dialog"
             aria-modal="true"
             aria-labelledby="addStrategyTitle"
             aria-describedby="addStrategyDescription"
             tabindex="-1">

        <div class="modal-header">
            <div>
                <h2 id="addStrategyTitle" class="modal-title">
                    Add Strategy
                </h2>

                <p id="addStrategyDescription" class="modal-subtitle">
                    Create a new carbon mitigation action.
                </p>
            </div>

            <button type="button"
                    data-close-modal
                    aria-label="Close modal"
                    class="modal-close-button">
                &times;
            </button>
        </div>

        <form method="POST"
              action="{{ route('admin.mitigation.store') }}"
              class="modal-form">
            @csrf

            <div class="form-field">
                <label for="strategy_title" class="mitigation-label">
                    Strategy Title <span class="required-mark">*</span>
                </label>

                <input id="strategy_title"
                       type="text"
                       name="title"
                       value="{{ old('title') }}"
                       placeholder="e.g. Campus energy conservation"
                       maxlength="255"
                       required
                       class="mitigation-input">

                @error('title')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="strategy_description" class="mitigation-label">
                    Description
                </label>

                <textarea id="strategy_description"
                          name="description"
                          rows="3"
                          placeholder="Describe the mitigation strategy..."
                          class="mitigation-input">{{ old('description') }}</textarea>

                @error('description')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="carbon_reduced" class="mitigation-label">
                    Carbon Reduced (kg CO₂e)
                    <span class="required-mark">*</span>
                </label>

                <input id="carbon_reduced"
                       type="number"
                       name="carbon_reduced"
                       value="{{ old('carbon_reduced', '0.00') }}"
                       min="0"
                       step="0.01"
                       required
                       class="mitigation-input">

                @error('carbon_reduced')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="strategy_status" class="mitigation-label">
                    Status <span class="required-mark">*</span>
                </label>

                <select id="strategy_status"
                        name="status"
                        required
                        class="mitigation-input">
                    <option value="pending"
                        @selected(old('status', 'pending') === 'pending')>
                        Pending
                    </option>

                    <option value="in_progress"
                        @selected(old('status') === 'in_progress')>
                        Active
                    </option>

                    <option value="completed"
                        @selected(old('status') === 'completed')>
                        Completed
                    </option>
                </select>

                @error('status')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="completed_at" class="mitigation-label">
                    Completed Date
                </label>

                <input id="completed_at"
                       type="date"
                       name="completed_at"
                       value="{{ old('completed_at') }}"
                       class="mitigation-input">

                <p class="field-hint">
                    Leave blank if the strategy has not been completed.
                </p>

                @error('completed_at')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="modal-footer">
                <button type="button"
                        data-close-modal
                        class="button-secondary">
                    Cancel
                </button>

                <button type="submit" class="button-primary">
                    Save Strategy
                </button>
            </div>
        </form>
    </section>
</div>

<style>
/* =========================================================
   1. GLOBAL THEME VARIABLES
   IMPORTANT: These variables are on :root, not just
   .mitigation-page, so the modal can access them too.
   ========================================================= */

:root {
    color-scheme: dark;

    --mit-bg: #0b120e;
    --mit-surface: #111a15;
    --mit-surface-raised: #17231c;
    --mit-input-bg: #0b120e;
    --mit-border: #293b31;
    --mit-border-strong: #35483b;
    --mit-text: #f3f4f6;
    --mit-text-secondary: #a8b5ab;
    --mit-text-muted: #829087;
    --mit-hover: #1a2a20;
    --mit-green: #168238;
    --mit-green-hover: #126d2e;
    --mit-focus: #22a447;
}

/* Support common light-mode class and data-attribute conventions. */
html.light,
body.light,
html.light-mode,
body.light-mode,
html[data-theme="light"],
body[data-theme="light"],
html[data-mode="light"],
body[data-mode="light"] {
    color-scheme: light;

    --mit-bg: #f9fafb;
    --mit-surface: #ffffff;
    --mit-surface-raised: #f3f6f4;
    --mit-input-bg: #ffffff;
    --mit-border: #e5e7eb;
    --mit-border-strong: #d1d5db;
    --mit-text: #111827;
    --mit-text-secondary: #4b5563;
    --mit-text-muted: #6b7280;
    --mit-hover: #f3f6f4;
    --mit-green: #15803d;
    --mit-green-hover: #166534;
    --mit-focus: #15803d;
}

/* If your admin layout uses the Tailwind dark class. */
html.dark,
body.dark,
html[data-theme="dark"],
body[data-theme="dark"],
html[data-mode="dark"],
body[data-mode="dark"] {
    color-scheme: dark;

    --mit-bg: #0b120e;
    --mit-surface: #111a15;
    --mit-surface-raised: #17231c;
    --mit-input-bg: #0b120e;
    --mit-border: #293b31;
    --mit-border-strong: #35483b;
    --mit-text: #f3f4f6;
    --mit-text-secondary: #a8b5ab;
    --mit-text-muted: #829087;
    --mit-hover: #1a2a20;
    --mit-green: #168238;
    --mit-green-hover: #126d2e;
    --mit-focus: #22a447;
}

/* =========================================================
   2. PAGE TYPOGRAPHY AND LAYOUT
   ========================================================= */

.mitigation-page,
.mitigation-modal,
.strategy-action-menu {
    font-family: Inter, ui-sans-serif, system-ui, -apple-system,
        BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 14px;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.mitigation-page *,
.mitigation-modal *,
.strategy-action-menu * {
    box-sizing: border-box;
}

.mitigation-page {
    min-width: 0;
    color: var(--mit-text);
}

.mitigation-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.mitigation-page-title {
    margin: 0;
    color: var(--mit-text);
    font-size: 21px;
    font-weight: 750;
    letter-spacing: -0.035em;
    line-height: 1.35;
}

.mitigation-page-subtitle {
    margin-top: 6px;
    color: var(--mit-text-secondary);
    font-size: 13px;
    line-height: 1.6;
}

.add-strategy-button {
    display: inline-flex;
    min-height: 42px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 10px 17px;
    border: 1px solid #168238;
    border-radius: 9px;
    background: #168238;
    color: #ffffff;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.3;
    cursor: pointer;
    box-shadow: 0 2px 5px rgb(0 0 0 / 10%);
    transition: background .15s, box-shadow .15s, transform .15s;
}

.add-strategy-button > span {
    font-size: 20px;
    font-weight: 400;
    line-height: 1;
}

.add-strategy-button:hover {
    background: #126d2e;
    box-shadow: 0 4px 10px rgb(0 0 0 / 12%);
}

.add-strategy-button:active {
    transform: translateY(1px);
}

.add-strategy-button:focus-visible,
.modal-close-button:focus-visible,
.action-menu-trigger:focus-visible,
.pagination-link:focus-visible,
.button-primary:focus-visible,
.button-secondary:focus-visible,
.delete-strategy-button:focus-visible {
    outline: 2px solid var(--mit-focus);
    outline-offset: 3px;
}

/* =========================================================
   3. FLASH MESSAGES
   ========================================================= */

.mitigation-success,
.mitigation-error {
    padding: 13px 16px;
    border: 1px solid;
    border-radius: 9px;
    font-size: 13px;
    line-height: 1.6;
}

.mitigation-success {
    border-color: #245b38;
    background: #102719;
    color: #a7f3bd;
}

.mitigation-error {
    border-color: #653030;
    background: #2b1111;
    color: #fecaca;
}

html.light .mitigation-success,
body.light .mitigation-success,
html.light-mode .mitigation-success,
body.light-mode .mitigation-success,
html[data-theme="light"] .mitigation-success,
body[data-theme="light"] .mitigation-success,
html[data-mode="light"] .mitigation-success,
body[data-mode="light"] .mitigation-success {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

html.light .mitigation-error,
body.light .mitigation-error,
html.light-mode .mitigation-error,
body.light-mode .mitigation-error,
html[data-theme="light"] .mitigation-error,
body[data-theme="light"] .mitigation-error,
html[data-mode="light"] .mitigation-error,
body[data-mode="light"] .mitigation-error {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

/* =========================================================
   4. MAIN CARD AND FILTER TABS
   ========================================================= */

.mitigation-card {
    min-width: 0;
    overflow: visible;
    border: 1px solid var(--mit-border);
    border-radius: 12px;
    background: var(--mit-surface);
    color: var(--mit-text);
    box-shadow: 0 5px 20px rgb(0 0 0 / 7%);
}

.mitigation-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 26px;
    padding: 0 24px;
    border-bottom: 1px solid var(--mit-border);
}

.mitigation-tab {
    display: inline-flex;
    min-height: 48px;
    align-items: center;
    margin-bottom: -1px;
    border-bottom: 2px solid transparent;
    color: var(--mit-text-secondary);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: color .15s, border-color .15s;
}

.mitigation-tab:hover,
.mitigation-tab.is-active {
    color: var(--mit-focus);
}

.mitigation-tab.is-active {
    border-bottom-color: var(--mit-focus);
}

/* =========================================================
   5. TABLE
   ========================================================= */

.mitigation-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    scrollbar-width: thin;
    scrollbar-color: var(--mit-border-strong) transparent;
}

.mitigation-table {
    width: 100%;
    min-width: 850px;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 13px;
}

.mitigation-table thead {
    background: var(--mit-surface-raised);
    color: var(--mit-text-secondary);
}

.mitigation-table th {
    padding: 15px 20px;
    border-bottom: 1px solid var(--mit-border);
    font-size: 11px;
    font-weight: 750;
    letter-spacing: .055em;
    text-transform: uppercase;
    white-space: nowrap;
}

.mitigation-table td {
    padding: 17px 20px;
    border-bottom: 1px solid var(--mit-border);
    color: var(--mit-text-secondary);
    vertical-align: middle;
}

.mitigation-table tbody tr:last-child td {
    border-bottom: 0;
}

.mitigation-table tbody tr {
    transition: background .15s;
}

.mitigation-table tbody tr:hover {
    background: var(--mit-hover);
}

.mitigation-table .strategy-cell {
    width: 25%;
    min-width: 190px;
    max-width: 310px;
}

.strategy-title,
.creator-name {
    color: var(--mit-text);
    font-size: 13px;
    font-weight: 650;
    overflow-wrap: anywhere;
}

.strategy-description {
    margin-top: 5px;
    color: var(--mit-text-secondary);
    font-size: 12px;
    line-height: 1.65;
    overflow-wrap: anywhere;
}

.creator-email {
    margin-top: 4px;
    color: var(--mit-text-muted);
    font-size: 11px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.carbon-value {
    color: var(--mit-text);
    font-weight: 750;
    font-variant-numeric: tabular-nums;
}

.carbon-unit {
    color: var(--mit-text-muted);
    font-size: 11px;
    white-space: nowrap;
}

.completed-date {
    color: var(--mit-text-secondary);
    white-space: nowrap;
}

.muted-text {
    color: var(--mit-text-muted);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 750;
    line-height: 1.3;
    white-space: nowrap;
}

.status-pending {
    background: #382b12;
    color: #fcd34d;
}

.status-active {
    background: #172c45;
    color: #93c5fd;
}

.status-completed {
    background: #143822;
    color: #86efac;
}

/* Status colors in light mode */
html.light .status-pending,
body.light .status-pending,
html.light-mode .status-pending,
body.light-mode .status-pending,
html[data-theme="light"] .status-pending,
body[data-theme="light"] .status-pending,
html[data-mode="light"] .status-pending,
body[data-mode="light"] .status-pending {
    background: #fef3c7;
    color: #92400e;
}

html.light .status-active,
body.light .status-active,
html.light-mode .status-active,
body.light-mode .status-active,
html[data-theme="light"] .status-active,
body[data-theme="light"] .status-active,
html[data-mode="light"] .status-active,
body[data-mode="light"] .status-active {
    background: #dbeafe;
    color: #1d4ed8;
}

html.light .status-completed,
body.light .status-completed,
html.light-mode .status-completed,
body.light-mode .status-completed,
html[data-theme="light"] .status-completed,
body[data-theme="light"] .status-completed,
html[data-mode="light"] .status-completed,
body[data-mode="light"] .status-completed {
    background: #dcfce7;
    color: #166534;
}

/* =========================================================
   6. ACTION DROPDOWN
   ========================================================= */

.action-cell {
    width: 85px;
    text-align: center;
}

.action-menu-trigger {
    display: inline-flex;
    width: 36px;
    height: 36px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--mit-border-strong);
    border-radius: 8px;
    background: var(--mit-surface);
    color: var(--mit-text-secondary);
    font-family: inherit;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    transition: border-color .15s, background .15s, color .15s;
}

.action-menu-trigger:hover,
.action-menu-trigger[aria-expanded="true"] {
    border-color: var(--mit-focus);
    background: var(--mit-hover);
    color: var(--mit-focus);
}

.strategy-action-menu {
    position: fixed;
    z-index: 11000;
    width: 190px;
    padding: 5px;
    border: 1px solid var(--mit-border-strong);
    border-radius: 10px;
    background: var(--mit-surface);
    color: var(--mit-text);
    box-shadow: 0 12px 32px rgb(0 0 0 / 25%);
}

.strategy-action-menu[hidden] {
    display: none !important;
}

.delete-strategy-button {
    display: flex;
    width: 100%;
    min-height: 40px;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: #fca5a5;
    text-align: left;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}

.delete-strategy-button:hover {
    background: #351919;
}

html.light .delete-strategy-button,
body.light .delete-strategy-button,
html.light-mode .delete-strategy-button,
body.light-mode .delete-strategy-button,
html[data-theme="light"] .delete-strategy-button,
body[data-theme="light"] .delete-strategy-button,
html[data-mode="light"] .delete-strategy-button,
body[data-mode="light"] .delete-strategy-button {
    color: #b91c1c;
}

html.light .delete-strategy-button:hover,
body.light .delete-strategy-button:hover,
html.light-mode .delete-strategy-button:hover,
body.light-mode .delete-strategy-button:hover,
html[data-theme="light"] .delete-strategy-button:hover,
body[data-theme="light"] .delete-strategy-button:hover,
html[data-mode="light"] .delete-strategy-button:hover,
body[data-mode="light"] .delete-strategy-button:hover {
    background: #fef2f2;
}

/* =========================================================
   7. EMPTY STATE
   ========================================================= */

.empty-cell {
    padding: 55px 24px !important;
    text-align: center;
}

.empty-state {
    max-width: 360px;
    margin: 0 auto;
}

.empty-icon {
    margin-bottom: 12px;
    font-size: 30px;
}

.empty-title {
    color: var(--mit-text);
    font-size: 15px;
    font-weight: 750;
}

.empty-description {
    margin-top: 7px;
    color: var(--mit-text-secondary);
    font-size: 13px;
    line-height: 1.7;
}

/* =========================================================
   8. PAGINATION
   ========================================================= */

.mitigation-pagination {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 24px;
    border-top: 1px solid var(--mit-border);
}

.pagination-summary {
    color: var(--mit-text-secondary);
    font-size: 12px;
}

.pagination-summary strong {
    color: var(--mit-text);
    font-weight: 750;
}

.pagination-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
}

.pagination-link,
.pagination-disabled,
.pagination-current {
    display: inline-flex;
    min-width: 35px;
    min-height: 35px;
    align-items: center;
    justify-content: center;
    padding: 7px 10px;
    border: 1px solid var(--mit-border-strong);
    border-radius: 7px;
    font-family: inherit;
    font-size: 12px;
    line-height: 1.3;
    text-decoration: none;
}

.pagination-link {
    background: var(--mit-surface);
    color: var(--mit-text-secondary);
    font-weight: 600;
    transition: background .15s, border-color .15s, color .15s;
}

.pagination-link:hover {
    border-color: var(--mit-focus);
    background: var(--mit-hover);
    color: var(--mit-focus);
}

.pagination-current {
    border-color: var(--mit-green);
    background: var(--mit-green);
    color: #ffffff;
    font-weight: 750;
}

.pagination-disabled {
    background: var(--mit-bg);
    color: var(--mit-text-muted);
    cursor: not-allowed;
    opacity: .8;
}

/* =========================================================
   9. MODAL
   The modal is outside .mitigation-page.
   All colors come from the GLOBAL variables above.
   An explicit solid background prevents transparency.
   ========================================================= */

.mitigation-modal[hidden] {
    display: none !important;
}

.mitigation-modal {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    isolation: isolate;
}

.mitigation-modal-backdrop {
    position: absolute;
    inset: 0;
    z-index: 0;
    background: rgb(0 0 0 / 65%);
    -webkit-backdrop-filter: blur(4px);
    backdrop-filter: blur(4px);
}

.mitigation-modal-panel {
    position: relative;
    z-index: 1;
    display: flex;
    width: min(100%, 520px);
    max-height: calc(100vh - 40px);
    max-height: calc(100dvh - 40px);
    flex-direction: column;
    overflow: hidden;

    /* Solid backgrounds are intentional: no transparent modal. */
    background-color: var(--mit-surface);
    color: var(--mit-text);
    border: 1px solid var(--mit-border-strong);
    border-radius: 15px;

    box-shadow:
        0 24px 70px rgb(0 0 0 / 30%),
        0 4px 12px rgb(0 0 0 / 10%);
}

.modal-header {
    display: flex;
    flex-shrink: 0;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 24px;
    border-bottom: 1px solid var(--mit-border);
    background-color: var(--mit-surface);
}

.modal-title {
    margin: 0;
    color: var(--mit-text);
    font-size: 19px;
    font-weight: 750;
    letter-spacing: -.025em;
    line-height: 1.4;
}

.modal-subtitle {
    margin-top: 5px;
    color: var(--mit-text-secondary);
    font-size: 12px;
    line-height: 1.6;
}

.modal-close-button {
    display: inline-flex;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: var(--mit-text-secondary);
    font-family: inherit;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    transition: background .15s, color .15s;
}

.modal-close-button:hover {
    background: var(--mit-hover);
    color: var(--mit-text);
}

.modal-form {
    min-height: 0;
    overflow-y: auto;
    padding: 22px 24px 24px;
    background-color: var(--mit-surface);
    overscroll-behavior: contain;
}

.form-field {
    margin-bottom: 18px;
}

.form-field:last-of-type {
    margin-bottom: 0;
}

.mitigation-label {
    display: block;
    margin-bottom: 7px;
    color: var(--mit-text);
    font-size: 12px;
    font-weight: 650;
    line-height: 1.5;
}

.required-mark {
    color: #ef4444;
}

.mitigation-input {
    display: block;
    width: 100%;
    min-height: 43px;
    padding: 10px 12px;
    border: 1px solid var(--mit-border-strong);
    border-radius: 8px;

    /* Explicit opaque input background. */
    background-color: var(--mit-input-bg);
    color: var(--mit-text);

    font-family: inherit;
    font-size: 13px;
    line-height: 1.5;
    outline: none;

    transition: border-color .15s, box-shadow .15s;
}

.mitigation-input::placeholder {
    color: var(--mit-text-muted);
    opacity: 1;
}

.mitigation-input:focus {
    border-color: var(--mit-focus);
    box-shadow: 0 0 0 3px rgb(34 164 71 / 15%);
}

.mitigation-input option {
    background-color: var(--mit-surface);
    color: var(--mit-text);
}

textarea.mitigation-input {
    min-height: 92px;
    resize: vertical;
}

.field-hint {
    margin-top: 6px;
    color: var(--mit-text-muted);
    font-size: 11px;
    line-height: 1.6;
}

.field-error {
    margin-top: 5px;
    color: #dc2626;
    font-size: 12px;
    line-height: 1.5;
}

html.dark .field-error,
body.dark .field-error,
html[data-theme="dark"] .field-error,
body[data-theme="dark"] .field-error,
html[data-mode="dark"] .field-error,
body[data-mode="dark"] .field-error {
    color: #fca5a5;
}

.modal-footer {
    display: flex;
    flex-shrink: 0;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid var(--mit-border);
}

.button-secondary,
.button-primary {
    display: inline-flex;
    min-height: 42px;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    border-radius: 8px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.4;
    cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
}

.button-secondary {
    border: 1px solid var(--mit-border-strong);
    background-color: var(--mit-surface);
    color: var(--mit-text-secondary);
}

.button-secondary:hover {
    background-color: var(--mit-hover);
    color: var(--mit-text);
}

.button-primary {
    border: 1px solid #168238;
    background-color: #168238;
    color: #ffffff;
}

.button-primary:hover {
    border-color: #126d2e;
    background-color: #126d2e;
}

.button-primary:disabled,
.button-secondary:disabled {
    cursor: not-allowed;
    opacity: .6;
}

/* =========================================================
   10. RESPONSIVE DESIGN
   ========================================================= */

@media (max-width: 640px) {
    .mitigation-heading {
        align-items: stretch;
        gap: 14px;
    }

    .mitigation-page-title {
        font-size: 19px;
    }

    .add-strategy-button {
        width: 100%;
    }

    .mitigation-tabs {
        gap: 20px;
        padding-right: 16px;
        padding-left: 16px;
    }

    .mitigation-table th,
    .mitigation-table td {
        padding: 14px 15px;
    }

    .mitigation-pagination {
        align-items: flex-start;
        padding: 15px 16px;
    }

    .pagination-controls {
        max-width: 100%;
    }

    .pagination-link,
    .pagination-disabled,
    .pagination-current {
        min-width: 32px;
        min-height: 33px;
        padding: 6px 8px;
        font-size: 11px;
    }

    .mitigation-modal {
        padding: 10px;
    }

    .mitigation-modal-panel {
        max-height: calc(100vh - 20px);
        max-height: calc(100dvh - 20px);
        border-radius: 12px;
    }

    .modal-header {
        padding: 18px;
    }

    .modal-form {
        padding: 18px;
    }

    .modal-footer {
        flex-direction: column-reverse;
    }

    .button-secondary,
    .button-primary {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .mitigation-page *,
    .mitigation-modal *,
    .strategy-action-menu * {
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('addModal');
    const openButton = document.getElementById('openAddModal');

    if (!modal) return;

    const closeButtons = modal.querySelectorAll('[data-close-modal]');
    const modalPanel = modal.querySelector('.mitigation-modal-panel');

    let previousFocus = null;
    let previousBodyOverflow = '';

    function closeAllActionMenus() {
        document.querySelectorAll('.strategy-action-menu').forEach(function (menu) {
            menu.hidden = true;
        });

        document.querySelectorAll('.action-menu-trigger').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
    }

    function openModal() {
        if (!modal.hidden) return;

        closeAllActionMenus();

        previousFocus = document.activeElement;
        previousBodyOverflow = document.body.style.overflow;

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(function () {
            const firstInput = modal.querySelector('[name="title"]');

            if (firstInput) {
                firstInput.focus();
            } else if (modalPanel) {
                modalPanel.focus();
            }
        });
    }

    function closeModal() {
        if (modal.hidden) return;

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = previousBodyOverflow;

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    if (openButton) {
        openButton.addEventListener('click', openModal);
    }

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    // Action dropdown menus.
    document.querySelectorAll('.action-menu-trigger').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();

            const menu = document.getElementById(button.dataset.menuId);

            if (!menu) return;

            const wasOpen = !menu.hidden;

            closeAllActionMenus();

            if (wasOpen) return;

            menu.hidden = false;
            button.setAttribute('aria-expanded', 'true');

            const rect = button.getBoundingClientRect();
            const menuWidth = menu.offsetWidth || 190;
            const menuHeight = menu.offsetHeight || 55;
            const margin = 8;
            const gap = 6;

            let left = rect.right - menuWidth;

            left = Math.max(
                margin,
                Math.min(left, window.innerWidth - menuWidth - margin)
            );

            let top = rect.bottom + gap;

            if (top + menuHeight > window.innerHeight - margin) {
                top = rect.top - menuHeight - gap;
            }

            top = Math.max(
                margin,
                Math.min(top, window.innerHeight - menuHeight - margin)
            );

            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
        });
    });

    document.addEventListener('click', function (event) {
        if (
            !event.target.closest('.strategy-action-menu') &&
            !event.target.closest('.action-menu-trigger')
        ) {
            closeAllActionMenus();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
            closeAllActionMenus();
        }

        // Keep keyboard focus inside the open modal.
        if (event.key === 'Tab' && !modal.hidden && modalPanel) {
            const focusable = Array.from(
                modalPanel.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), ' +
                    'select:not([disabled]), textarea:not([disabled]), ' +
                    '[tabindex]:not([tabindex="-1"])'
                )
            ).filter(function (element) {
                return element.getClientRects().length > 0;
            });

            if (!focusable.length) {
                event.preventDefault();
                modalPanel.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (
                !event.shiftKey &&
                document.activeElement === last
            ) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    // Close menus on scroll or resize to prevent detached positioning.
    window.addEventListener('scroll', closeAllActionMenus, true);
    window.addEventListener('resize', closeAllActionMenus);

    // Reopen the form after Laravel validation errors.
    if (modal.dataset.openOnLoad === 'true') {
        openModal();
    }
});
</script>
@endsection
