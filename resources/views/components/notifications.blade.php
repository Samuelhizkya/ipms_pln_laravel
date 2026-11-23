@php
    $notifications = [];

    $validationMessages = $errors->all();

    $importMessages = array_merge(
        (array) session('import_errors', []),
        (array) session('errors_import', [])
    );

    $errorMessages = array_merge(
        $validationMessages,
        $importMessages,
        session('error') ? [(string) session('error')] : []
    );

    $errorMessages = array_values(array_unique(array_filter(
        $errorMessages,
        fn ($message) => is_string($message) && trim($message) !== ''
    )));

    if (count($errorMessages) > 0) {
        $notifications[] = [
            'icon' => 'error',
            'title' => 'Periksa kembali',
            'text' => implode("\n", $errorMessages),
        ];
    }

    foreach ([
        'warning' => ['warning', 'Perhatian'],
        'info' => ['info', 'Informasi'],
        'success' => ['success', 'Berhasil'],
    ] as $key => [$icon, $title]) {
        if (session($key)) {
            $notifications[] = [
                'icon' => $icon,
                'title' => $title,
                'text' => (string) session($key),
            ];
        }
    }
@endphp

<div
    id="ipms-notifications"
    data-messages="{{ json_encode(
        $notifications,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ) }}"
    hidden
></div>