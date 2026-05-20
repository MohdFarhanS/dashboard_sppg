@if ($paginator->hasPages())
    <nav class="d-flex align-items-center justify-content-between py-2" aria-label="Pagination">
        <p class="small text-muted mb-0">
            Menampilkan
            <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
            &ndash;
            <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            dari
            <span class="fw-semibold">{{ $paginator->total() }}</span>
            data
        </p>

        <ul class="pagination pagination-sm mb-0">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link"><i class="fas fa-chevron-left fa-xs"></i></span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">
                        <i class="fas fa-chevron-left fa-xs"></i>
                    </a>
                </li>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">
                        <i class="fas fa-chevron-right fa-xs"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link"><i class="fas fa-chevron-right fa-xs"></i></span>
                </li>
            @endif
        </ul>
    </nav>
@endif
