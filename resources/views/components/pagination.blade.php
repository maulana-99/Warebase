@if ($paginator->hasPages())
    <nav aria-label="Navigasi halaman" class="flex items-center gap-3 text-sm">
        <p class="text-muted">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</p>
        @if ($paginator->onFirstPage())
            <span class="icon-btn opacity-40" aria-hidden="true"><x-icon name="chevron-left" class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="icon-btn" aria-label="Halaman sebelumnya"><x-icon name="chevron-left" class="size-4" /></a>
        @endif
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="icon-btn" aria-label="Halaman berikutnya"><x-icon name="chevron-right" class="size-4" /></a>
        @else
            <span class="icon-btn opacity-40" aria-hidden="true"><x-icon name="chevron-right" class="size-4" /></span>
        @endif
    </nav>
@endif
