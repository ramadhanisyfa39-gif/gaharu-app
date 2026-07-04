{{-- ════════════════════════════════════════════════════════════
     forgot-password.blade.php
     ════════════════════════════════════════════════════════════ --}}
{{--  SAVE AS: resources/views/auth/forgot-password.blade.php   --}}

<x-guest-layout>

    @if(session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    <h1 class="auth-title">Reset password</h1>
    <p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input id="email" class="form-input" type="email" name="email"
                   value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
            @error('email')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-primary">Send reset link</button>

        <a href="{{ route('login') }}" class="btn-secondary">← Back to login</a>
    </form>

</x-guest-layout>