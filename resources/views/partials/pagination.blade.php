@php
    $lastPage = $paginator->lastPage();
    $current = $paginator->currentPage();

    $visible = array_values(array_unique(array_filter(
        [$lastPage > 1 ? 1 : null, $current - 1, $current, $current + 1, $lastPage > 1 ? $lastPage : null],
        fn ($page) => $page >= 1 && $page <= $lastPage,
    )));
    sort($visible);

    $previousRendered = 0;

    // Page 1 links stay on the clean path — never a "?page=1" query string.
    $pageUrl = static function (int $page) use ($paginator): string {
        if ($page <= 1) {
            $query = $paginator->query ?? [];

            unset($query[$paginator->getPageName()]);

            return $query === []
                ? $paginator->path()
                : $paginator->path().'?'.http_build_query($query);
        }

        return $paginator->url($page);
    };
@endphp

@if ($lastPage > 1)
    <nav role="navigation" aria-label="Pagination" class="pager-compact">
        <p class="pager-compact-info">
            Showing <strong>{{ number_format($paginator->firstItem()) }}</strong> to <strong>{{ number_format(min($paginator->lastItem(), $paginator->total())) }}</strong>
            of <strong>{{ number_format($paginator->total()) }}</strong> results
        </p>

        <div class="pager-compact-pages">
            @if ($paginator->onFirstPage())
                <span class="pager-compact-arrow is-disabled" aria-disabled="true">Prev</span>
            @else
                <a class="pager-compact-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Prev</a>
            @endif

            @foreach ($visible as $page)
                @if ($page - $previousRendered > 1)
                    <span class="pager-compact-dots" aria-hidden="true">&hellip;</span>
                @endif

                @if ($page === $current)
                    <span class="pager-compact-page is-active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pager-compact-page" href="{{ $pageUrl($page) }}">{{ $page }}</a>
                @endif

                @php $previousRendered = $page; @endphp
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pager-compact-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rarr;</a>
            @else
                <span class="pager-compact-arrow is-disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
