@props(['name'])

@error($name)
    <p id="{{ $name }}-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
@enderror
