@if ($paginator->hasPages())
    <ul class="uk-pagination" uk-margin>
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="uk-disabled">
                <span>@lang('pagination.previous')</span>
            </li>
        @else
            <li>
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
            </li>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a>
            </li>
        @else
            <li class="uk-disabled">
                <span>@lang('pagination.next')</span>
            </li>
        @endif
    </ul>
@endif

