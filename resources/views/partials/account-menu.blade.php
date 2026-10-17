<div class="topbar-actions">
    <div
        data-account-menu-root
        data-user-name="{{ explode(' ', trim(auth()->user()->name))[0] }}"
        data-password-url="{{ route('password.update') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-current-password-error="{{ $errors->first('current_password') }}"
        data-new-password-error="{{ $errors->first('password') }}"
        data-status-message="{{ session('password-status', '') }}"
    >
        <button class="topbar-user" type="button" aria-expanded="false" aria-controls="account-menu-dropdown">
            <span class="topbar-avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span>{{ explode(' ', trim(auth()->user()->name))[0] }}</span>
            <svg class="chevron-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
        </button>
    </div>
</div>
