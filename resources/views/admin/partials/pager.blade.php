@if ($p->hasPages())
    <div class="flex items-center justify-between gap-3 border-t border-surface-container px-5 py-3 text-body-sm text-on-surface-variant">
        <span>{{ $p->firstItem() }}–{{ $p->lastItem() }} of {{ $p->total() }}</span>
        <div class="flex gap-2">
            @if ($p->onFirstPage()) <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Previous</span> @else <a href="{{ $p->previousPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Previous</a> @endif
            @if ($p->hasMorePages()) <a href="{{ $p->nextPageUrl() }}" class="rounded-lg bg-surface-container-low px-3 py-1.5 hover:bg-surface-container-high">Next</a> @else <span class="rounded-lg bg-surface-container px-3 py-1.5 opacity-50">Next</span> @endif
        </div>
    </div>
@endif