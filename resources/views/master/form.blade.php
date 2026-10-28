@php($editing = $item->exists)

<x-layouts.app :heading="($editing ? 'Ubah ' : 'Tambah ').$config['singular']">
    <form method="POST" action="{{ $editing ? route('master.update', [$type, $item->id]) : route('master.store', $type) }}" class="card max-w-3xl" novalidate
          @if ($editing) data-confirm="Perubahan data {{ strtolower($config['singular']) }} {{ $item->name }} akan disimpan." data-confirm-title="Simpan perubahan?" data-confirm-button="Ya, simpan" data-confirm-tone="neutral" @endif>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5">
            @foreach ($config['fields'] as $name => $field)
                <div>
                    <label for="{{ $name }}" class="label">{{ $field['label'] }}</label>
                    @if ($field['textarea'] ?? false)
                        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] }}" class="input"
                                  @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>{{ old($name, $item->{$name}) }}</textarea>
                    @else
                        <input id="{{ $name }}" name="{{ $name }}" type="{{ ($field['email'] ?? false) ? 'email' : 'text' }}" value="{{ old($name, $item->{$name}) }}"
                               maxlength="{{ $field['max'] }}" autocomplete="off" @required($field['required'] ?? false) @if ($loop->first) autofocus @endif class="input"
                               @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
                    @endif
                    @isset($field['hint'])<p class="mt-1.5 text-xs text-subtle">{{ $field['hint'] }}</p>@endisset
                    <x-field-error :name="$name" />
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ route('master.list', $type) }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">{{ $editing ? 'Simpan perubahan' : 'Simpan' }}</button>
        </div>
    </form>
</x-layouts.app>
