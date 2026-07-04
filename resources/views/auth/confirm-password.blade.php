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