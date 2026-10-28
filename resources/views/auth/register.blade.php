<x-layouts.guest title="Daftar">
    <h1 class="text-3xl font-bold">Buat akun</h1>
    <p class="mt-2 text-sm text-muted">Akun baru terdaftar sebagai Staff Gudang.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5" novalidate>
        @csrf

        <div>
            <label for="name" class="label">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            <x-field-error name="name" />
        </div>

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                   class="input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <x-field-error name="email" />
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" aria-describedby="password-hint"
                   class="input" @error('password') aria-invalid="true" @enderror>
            <p id="password-hint" class="mt-1.5 text-xs text-subtle">Minimal 8 karakter, berisi huruf dan angka.</p>
            <x-field-error name="password" />
        </div>

        <div>
            <label for="password_confirmation" class="label">Ulangi password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
        </div>

        <button type="submit" class="btn-primary w-full">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-ink underline-offset-4 hover:underline">Masuk</a>
    </p>
</x-layouts.guest>
