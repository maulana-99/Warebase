<nav aria-label="Data master" class="mb-5 flex flex-wrap gap-2">
    @foreach (\App\Http\Requests\Master\MasterDataRequest::TYPES as $key => $tab)
        <a href="{{ route('master.list', $key) }}" @if ($key === $type) aria-current="page" @endif
           @class(['badge px-4 py-2 text-sm transition', 'bg-sky text-on-accent' => $key === $type, 'bg-raised text-muted hover:text-ink' => $key !== $type])>{{ $tab['title'] }}</a>
    @endforeach
</nav>
