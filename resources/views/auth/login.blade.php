<x-guest-layout>

    {{-- Session Status --}}
    @if(session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Username --}}
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input
                id="username"
                class="form-input"
                type="text"
                name="username"
                value="{{ old('username') }}"
                placeholder="Enter your username"
                required
                autofocus
                autocomplete="username"
            >
            @error('username')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input
                id="password"
                class="form-input"
                type="password"
                name="password"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
            >
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember & Forgot --}}
        <div class="check-row">
            <label class="check-label">
                <input type="checkbox" name="remember">
                Remember me
            </label>
            @if(Route::has('password.request'))
                <a class="forgot-link" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary">
            Log in
        </button>

    </form>

</x-guest-layout>