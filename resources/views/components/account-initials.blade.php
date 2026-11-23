@php
    $initialName = trim((string) ($name ?? 'Pengguna'));

    $initialWords = preg_split(
        '/\s+/u',
        $initialName,
        -1,
        PREG_SPLIT_NO_EMPTY
    ) ?: [];

    if (count($initialWords) >= 2) {
        $accountInitials =
            mb_substr($initialWords[0], 0, 1)
            . mb_substr($initialWords[1], 0, 1);
    } else {
        $accountInitials = mb_substr(
            $initialWords[0] ?? 'Pengguna',
            0,
            1
        );
    }

    $accountInitials = mb_strtoupper($accountInitials);
@endphp
{{ $accountInitials }}