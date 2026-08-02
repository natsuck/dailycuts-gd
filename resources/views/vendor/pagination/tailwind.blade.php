@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation">

        {{-- Mobile: Previous / Next --}}
        <div class="flex gap-2 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface-variant/40 bg-surface-container-lowest border border-outline-variant cursor-not-allowed rounded-lg">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface bg-surface-container-lowest border border-outline-variant rounded-lg hover:bg-surface-container-low active:scale-95 transition-all">
                    Previous
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface bg-surface-container-lowest border border-outline-variant rounded-lg hover:bg-surface-container-low active:scale-95 transition-all">
                    Next
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-on-surface-variant/40 bg-surface-container-lowest border border-outline-variant cursor-not-allowed rounded-lg">
                    Next
                </span>
            @endif
        </div>

        {{-- Desktop: Page numbers --}}
        <div class="hidden sm:flex sm:items-center sm:justify-center">
            <span class="inline-flex items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-on-surface-variant/40 bg-surface-container-lowest border border-outline-variant rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-on-surface bg-surface-container-lowest border border-outline-variant rounded-lg hover:bg-surface-container-low active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif

                {{-- Page Numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true" class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-on-surface-variant cursor-default">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-lg bg-primary">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-on-surface bg-surface-container-lowest border border-outline-variant rounded-lg hover:bg-surface-container-low active:scale-95 transition-all" aria-label="Go to page {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-on-surface bg-surface-container-lowest border border-outline-variant rounded-lg hover:bg-surface-container-low active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span aria-disabled="true" class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-on-surface-variant/40 bg-surface-container-lowest border border-outline-variant rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </span>
        </div>

        {{-- Info --}}
        <div class="mt-4 text-center">
            <p class="text-sm text-on-surface-variant">
                Showing <span class="font-medium">{{ $paginator->firstItem() }}</span> to <span class="font-medium">{{ $paginator->lastItem() }}</span> of <span class="font-medium">{{ $paginator->total() }}</span> results
            </p>
        </div>
    </nav>
@endif
