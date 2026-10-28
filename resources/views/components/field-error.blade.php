@props(['name'])

@error($name)
    <p id="{{ $name }}-error" class="mt-1.5 text-sm text-rose">{{ $message }}</p>
@enderror
