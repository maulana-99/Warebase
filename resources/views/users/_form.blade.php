@php($editing = $user->exists)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="label">Nama lengkap</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="off"
               class="input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        <x-field-error name="name" />
    </div>

    <div>
        <label for="email" class="label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="off"
               class="input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        <x-field-error name="email" />
    </div>

    <fieldset class="md:col-span-2">
        <legend class="label">Peran</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (\App\Enums\Role::cases() as $role)
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-raised p-4 has-checked:border-sky">
                    <input type="radio" name="role" value="{{ $role->value }}" class="mt-1 accent-sky"
                           @checked(old('role', $user->role?->value) === $role->value)
                           @disabled($editing && $user->is(auth()->user()))>
                    <span>
                        <span class="block font-semibold">{{ $role->label() }}</span>
                        <span class="block text-xs text-muted">
                            {{ $role === \App\Enums\Role::Admin ? 'Kelola data master, pengguna, dan menyetujui permintaan.' : 'Mencatat barang masuk dan membuat permintaan barang.' }}
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
        @if ($editing && $user->is(auth()->user()))
            <input type="hidden" name="role" value="{{ $user->role->value }}">
            <p class="mt-2 text-xs text-subtle">Anda tidak dapat mengubah peran akun Anda sendiri.</p>
        @endif
        <x-field-error name="role" />
    </fieldset>

    <div>
        <label for="password" class="label">Password {{ $editing ? '(kosongkan jika tidak diubah)' : '' }}</label>
        <input id="password" name="password" type="password" {{ $editing ? '' : 'required' }} autocomplete="new-password" aria-describedby="password-hint"
               class="input" @error('password') aria-invalid="true" @enderror>
        <p id="password-hint" class="mt-1.5 text-xs text-subtle">Minimal 8 karakter, berisi huruf dan angka.</p>
        <x-field-error name="password" />
    </div>

    <div>
        <label for="password_confirmation" class="label">Ulangi password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
    </div>
</div>
