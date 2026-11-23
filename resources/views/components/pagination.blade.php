@if ($paginator->hasPages())
    <nav class="ipms-pagination" aria-label="Navigasi halaman tabel">
        <p class="ipms-pagination__summary">
            Menampilkan
            <strong>{{ $paginator->firstItem() }}</strong>
            –
            <strong>{{ $paginator->lastItem() }}</strong>
            dari
            <strong>{{ $paginator->total() }}</strong>
            data
        </p>

        <ul class="ipms-pagination__list">
            {{-- Halaman sebelumnya --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span
                        class="ipms-pagination__item is-disabled"
                        aria-disabled="true"
                    >
                        Sebelumnya
                    </span>
                @else
                    <a
                        class="ipms-pagination__item"
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                        aria-label="Buka halaman sebelumnya"
                    >
                        Sebelumnya
                    </a>
                @endif
            </li>

            {{-- Nomor halaman dan pemisah --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span
                            class="ipms-pagination__ellipsis"
                            aria-hidden="true"
                        >
                            {{ $element }}
                        </span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page === $paginator->currentPage())
                                <span
                                    class="ipms-pagination__item is-current"
                                    aria-current="page"
                                    aria-label="Halaman {{ $page }}, halaman saat ini"
                                >
                                    {{ $page }}
                                </span>
                            @else
                                <a
                                    class="ipms-pagination__item"
                                    href="{{ $url }}"
                                    aria-label="Buka halaman {{ $page }}"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Halaman berikutnya --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a
                        class="ipms-pagination__item"
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                        aria-label="Buka halaman berikutnya"
                    >
                        Berikutnya
                    </a>
                @else
                    <span
                        class="ipms-pagination__item is-disabled"
                        aria-disabled="true"
                    >
                        Berikutnya
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@elseif ($paginator->total() > 0)
    <p class="ipms-pagination__summary ipms-pagination__summary--single">
        Menampilkan
        <strong>{{ $paginator->total() }}</strong>
        data
    </p>
@endif