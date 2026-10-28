<x-layouts.guest title="Masuk">
    <h1 class="text-3xl font-bold">Selamat datang kembali</h1>
    <p class="mt-2 text-sm text-muted">Masuk untuk mengelola stok gudang.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" novalidate>
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <x-field-error name="email" />
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="input" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <x-field-error name="password" />
        </div>

        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded accent-sky" @checked(old('remember'))>
            Ingat saya
        </label>

        <button type="submit" class="btn-primary w-full">Masuk</button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-ink underline-offset-4 hover:underline">Daftar</a>
    </p>
</x-layouts.guest>
