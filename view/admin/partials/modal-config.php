<?php
if (!function_exists('adminModalAction')) {
    function adminModalAction(string $label, string $class, array $attrs = []): array
    {
        return [
            'class' => $class,
            'label' => $label,
            'attrs' => $attrs,
        ];
    }
}

if (!function_exists('adminConfirmModal')) {
    function adminConfirmModal(
        string $id,
        string $text,
        string $cancelLabel,
        string $confirmLabel,
        array $cancelAttrs = [],
        array $confirmAttrs = [],
        array $modalAttrs = [],
        array $textAttrs = []
    ): array {
        return [
            'id' => $id,
            'attrs' => $modalAttrs,
            'text' => $text,
            'text_attrs' => $textAttrs,
            'actions' => [
                adminModalAction($cancelLabel, 'btn btn-light', $cancelAttrs),
                adminModalAction($confirmLabel, 'btn btn-primary', $confirmAttrs),
            ],
        ];
    }
}

if (!function_exists('renderAdminModal')) {
    function renderAdminModal(array $modal): void
    {
        require __DIR__ . '/modal.php';
    }
}

if (!function_exists('renderAdminConfirmModal')) {
    function renderAdminConfirmModal(
        string $id,
        string $text,
        string $cancelLabel,
        string $confirmLabel,
        array $cancelAttrs = [],
        array $confirmAttrs = [],
        array $modalAttrs = [],
        array $textAttrs = []
    ): void {
        renderAdminModal(adminConfirmModal(
            $id,
            $text,
            $cancelLabel,
            $confirmLabel,
            $cancelAttrs,
            $confirmAttrs,
            $modalAttrs,
            $textAttrs
        ));
    }
}
