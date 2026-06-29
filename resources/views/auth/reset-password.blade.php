{{-- ════════════════════════════════════════════════════════════
     reset-password.blade.php
     SAVE AS: resources/views/auth/reset-password.blade.php
     ════════════════════════════════════════════════════════════ --}}

<x-guest-layout>

    <h1 class="auth-title">Set new password</h1>
    <p class="auth-subtitle">Choose a strong password for your account.</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input id="email" class="form-input" type="email" name="email"
                   value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password">New password</label>
            <input id="password" class="form-input" type="password" name="password"
                   placeholder="Minimum 8 characters" required autocomplete="new-password">
            @error('password')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" class="form-input" type="password"
                   name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-primary">Reset password</button>
    </form>

</x-guest-layout>


{{-- ════════════════════════════════════════════════════════════
     confirm-password.blade.php
     SAVE AS: resources/views/auth/confirm-password.blade.php
     ════════════════════════════════════════════════════════════ --}}

{{--
<x-guest-layout>

    <h1 class="auth-title">Confirm your password</h1>
    <p class="auth-subtitle">This is a secure area. Please re-enter your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input id="password" class="form-input" type="password" name="password"
                   required autocomplete="current-password">
            @error('password')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-primary">Confirm</button>
    </form>

</x-guest-layout>
--}}