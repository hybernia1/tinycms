<?php
$items = is_array($listItems ?? null) ? $listItems : [];
$listName = (string)($listName ?? 'list');
$listEndpoint = (string)($listEndpoint ?? '');
$listEditBase = (string)($listEditBase ?? '');
$listRootAttrs = is_array($listRootAttrs ?? null) ? $listRootAttrs : [];
$searchPlaceholder = (string)($searchPlaceholder ?? '');
$searchHidden = is_array($searchHidden ?? null) ? $searchHidden : [];
$perPageHidden = is_array($perPageHidden ?? null) ? $perPageHidden : [];
$listColumns = is_array($listColumns ?? null) ? $listColumns : [];
$listAllowedPerPage = is_array($listAllowedPerPage ?? null) ? $listAllowedPerPage : [];
$listPage = (int)($listPage ?? 1);
$listPerPage = (int)($listPerPage ?? \App\Service\Support\PaginationConfig::perPage());
$listTotalPages = (int)($listTotalPages ?? 1);
$listQuery = (string)($listQuery ?? '');
$statusEnabled = (bool)($statusEnabled ?? false);
$statusLinks = is_array($statusLinks ?? null) ? $statusLinks : [];
$statusCurrent = (string)($statusCurrent ?? 'all');
$statusUrl = is_callable($statusUrl ?? null) ? $statusUrl : null;
$paginationUrl = is_callable($paginationUrl ?? null) ? $paginationUrl : null;
$rowRenderer = is_callable($rowRenderer ?? null) ? $rowRenderer : null;
$deleteConfirmText = (string)($deleteConfirmText ?? '');
$csrfMarkup = (string)($csrfMarkup ?? '');
$listColumnsCount = max(1, count($listColumns));

$rootAttrs = [
    'data-' . $listName . '-list' => null,
    'data-endpoint' => $listEndpoint,
    'data-edit-base' => $listEditBase,
];
foreach ($listRootAttrs as $attr => $value) {
    $rootAttrs[(string)$attr] = $value;
}
?>
<div
<?php foreach ($rootAttrs as $attr => $value): ?>
    <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
<?php endforeach; ?>
>
    <div data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-csrf class="d-none"><?= $csrfMarkup ?></div>
    <div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
        <?php if ($statusEnabled): ?>
            <nav class="filter-nav">
                <?php foreach ($statusLinks as $key => $label): ?>
                    <a class="filter-link<?= $statusCurrent === (string)$key ? ' active' : '' ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-status="<?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($statusUrl !== null ? (string)$statusUrl((string)$key) : '#', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </nav>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
        <form method="get" class="search-form">
            <?php foreach ($searchHidden as $name => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
            <div class="search-field field-with-icon">
                <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($listQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-search>
                <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true"><?= $icon('search') ?></span>
            </div>
        </form>
    </div>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <?php foreach ($listColumns as $column): ?>
                        <?php
                        $label = (string)($column['label'] ?? '');
                        $class = trim((string)($column['class'] ?? ''));
                        ?>
                        <th<?= $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-list-body>
                <?php if ($items === []): ?>
                    <tr data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-empty-row>
                        <td colspan="<?= $listColumnsCount ?>" class="admin-list-empty">
                            <svg class="admin-list-empty-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2314 1698" aria-hidden="true">
                                <g transform="translate(0 1698) scale(0.1 -0.1)" fill="currentColor">
                                    <path d="M5842 16965 c-56 -24 -65 -64 -65 -260 0 -198 17 -321 73 -540 54 -212 56 -229 31 -303 -36 -107 -23 -221 51 -428 47 -132 84 -193 143 -234 85 -58 176 -63 293 -15 35 14 72 23 82 20 13 -4 27 -32 46 -87 17 -52 39 -94 61 -119 70 -77 225 -108 464 -92 164 11 221 30 252 82 9 16 34 37 54 46 33 16 44 17 93 6 123 -28 162 -32 241 -21 157 22 298 68 434 145 190 106 211 188 89 347 -118 153 -232 252 -356 307 -146 66 -444 137 -618 147 -121 7 -145 18 -160 73 -15 55 -32 66 -109 66 l-66 0 -29 55 c-130 249 -301 464 -489 615 -160 129 -428 228 -515 190z"/>
                                    <path d="M3275 15024 c-306 -73 -584 -264 -828 -569 -163 -203 -233 -332 -376 -690 -51 -126 -99 -240 -107 -252 -17 -27 -50 -29 -120 -8 -27 8 -63 15 -81 15 -33 0 -38 -4 -86 -80 -40 -62 -57 -61 -213 12 -208 98 -350 138 -563 159 -200 20 -370 -4 -506 -71 -126 -61 -279 -209 -347 -335 -30 -55 -33 -67 -33 -151 0 -82 3 -94 27 -131 15 -23 43 -53 62 -68 46 -35 223 -97 404 -141 197 -48 321 -93 487 -174 216 -105 370 -215 413 -296 5 -10 29 -77 52 -149 82 -253 131 -352 246 -501 152 -196 352 -331 574 -388 36 -9 117 -21 180 -27 99 -10 120 -9 152 4 67 28 101 105 118 265 22 198 59 244 158 193 20 -11 91 -63 157 -116 204 -162 305 -198 442 -157 206 63 384 285 438 547 36 174 28 494 -15 619 -42 123 -137 255 -296 411 -70 68 -115 121 -120 138 -10 43 21 76 111 120 89 43 167 116 205 193 24 49 24 51 24 324 l-1 275 -61 305 c-33 168 -68 355 -77 415 -35 233 -120 326 -294 324 -36 0 -93 -7 -126 -15z"/>
                                    <path d="M10865 14563 c-98 -26 -120 -68 -130 -243 -3 -71 -16 -171 -30 -230 -13 -58 -33 -169 -45 -248 -11 -79 -33 -183 -47 -230 -14 -48 -36 -130 -48 -182 -28 -117 -90 -302 -118 -353 -12 -21 -51 -62 -87 -91 -296 -240 -536 -563 -623 -842 -80 -253 -100 -605 -53 -906 65 -410 128 -541 429 -887 293 -337 396 -480 463 -646 64 -157 74 -281 28 -366 -47 -85 -127 -140 -242 -165 -179 -37 -414 2 -552 92 -84 55 -285 264 -389 404 -140 188 -186 262 -347 550 -374 674 -568 962 -823 1224 -274 281 -579 488 -911 620 -241 95 -375 117 -680 112 -167 -3 -221 -8 -290 -25 -229 -58 -488 -244 -624 -449 -58 -88 -137 -263 -160 -353 -44 -177 7 -350 128 -437 57 -41 96 -57 170 -71 69 -13 85 -25 125 -95 36 -65 88 -111 166 -147 115 -54 160 -61 445 -68 250 -7 269 -9 332 -33 126 -47 247 -135 432 -309 144 -136 250 -259 412 -475 191 -256 244 -339 511 -804 79 -136 311 -581 434 -831 123 -250 170 -321 294 -444 102 -102 236 -206 639 -496 126 -90 274 -201 330 -247 55 -45 124 -98 153 -117 28 -19 55 -40 58 -45 4 -6 17 -57 30 -113 97 -429 362 -1195 600 -1732 118 -266 418 -876 533 -1084 130 -235 350 -583 432 -686 17 -22 72 -83 122 -134 64 -67 100 -114 122 -160 28 -61 31 -74 30 -166 0 -88 -5 -113 -38 -210 -62 -185 -134 -303 -310 -512 -90 -108 -155 -162 -251 -210 -146 -74 -232 -140 -412 -316 -266 -261 -323 -345 -330 -497 -5 -98 5 -124 56 -155 48 -28 81 -64 121 -131 73 -121 338 -353 502 -439 93 -49 135 -63 275 -90 74 -14 154 -37 200 -58 l78 -34 290 12 c241 11 301 17 355 34 163 51 314 204 384 386 44 117 55 135 82 145 14 6 95 10 179 10 160 0 223 8 313 41 139 50 291 189 361 329 54 108 60 166 31 282 -25 99 -22 145 11 215 39 81 70 79 379 -32 100 -36 238 -81 308 -101 70 -20 133 -40 140 -46 20 -17 14 -43 -27 -125 -86 -168 -80 -420 14 -618 42 -88 65 -110 151 -141 38 -13 116 -53 174 -88 149 -91 231 -130 367 -175 191 -64 306 -84 448 -78 128 5 196 22 318 77 83 37 140 39 205 6 95 -48 356 -62 495 -26 228 58 450 234 538 426 41 90 63 104 164 104 102 0 178 25 252 81 29 23 53 44 53 48 0 3 24 32 53 64 40 42 60 57 80 57 15 0 112 -42 215 -93 285 -142 469 -197 729 -218 155 -12 339 -2 480 27 43 8 323 96 623 194 648 212 696 227 910 280 363 91 740 134 978 112 350 -33 675 -160 965 -378 47 -35 92 -64 101 -64 37 0 49 95 20 156 -37 77 -193 178 -464 299 -395 178 -843 260 -1240 226 -250 -21 -401 -47 -1025 -175 -279 -57 -559 -96 -745 -103 -237 -9 -514 39 -665 114 -120 61 -250 177 -332 298 -96 143 -108 191 -100 423 13 407 -31 839 -124 1206 -38 149 -147 494 -192 606 -55 136 -258 538 -346 683 -269 448 -652 885 -1043 1190 -249 194 -481 327 -793 452 -340 137 -614 327 -883 613 -70 74 -190 200 -267 281 -149 155 -213 245 -350 481 -43 74 -117 200 -165 280 -218 362 -303 597 -317 870 -13 275 39 384 232 489 125 69 185 119 405 340 442 444 582 685 635 1091 14 106 50 320 71 425 4 17 9 116 13 222 l6 192 42 58 c178 245 339 535 401 723 91 278 172 705 172 910 0 88 -15 182 -36 222 -49 96 -230 98 -549 8 -93 -26 -249 -69 -345 -95 -212 -57 -440 -125 -700 -210 -107 -35 -244 -77 -304 -93 -123 -35 -130 -34 -211 27 -126 97 -409 256 -567 320 -79 32 -173 72 -210 89 -141 64 -458 156 -654 188 -54 9 -185 19 -291 23 l-192 6 -35 34 c-23 22 -50 69 -77 134 -59 140 -128 227 -363 462 -276 275 -381 343 -546 350 -41 2 -84 1 -95 -2z"/>
                                </g>
                            </svg>
                            <div class="admin-list-empty-text"><?= htmlspecialchars($t('common.nothing_found'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <?= $rowRenderer !== null ? $rowRenderer((array)$row) : '' ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-between align-center mt-4">
            <?php if ($listTotalPages > 1): ?>
                <div class="pagination">
                    <?php $prevPage = max(1, $listPage - 1); $nextPage = min($listTotalPages, $listPage + 1); ?>
                    <a class="pagination-link<?= $listPage <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl !== null ? (string)$paginationUrl($prevPage) : '#', ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-prev<?= $listPage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span><?= htmlspecialchars($t('common.previous'), ENT_QUOTES, 'UTF-8') ?></span></a>
                    <a class="pagination-link<?= $listPage >= $listTotalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl !== null ? (string)$paginationUrl($nextPage) : '#', ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-next<?= $listPage >= $listTotalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span><?= htmlspecialchars($t('common.next'), ENT_QUOTES, 'UTF-8') ?></span><?= $icon('next') ?></a>
                </div>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <form method="get" class="d-flex gap-2 align-center">
                <select name="per_page" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-per-page>
                    <?php foreach ($listAllowedPerPage as $option): ?>
                        <option value="<?= (int)$option ?>" <?= $listPerPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
                    <?php endforeach; ?>
                </select>
                <?php foreach ($perPageHidden as $name => $value): ?>
                    <input type="hidden" name="<?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>">
                <?php endforeach; ?>
                <button class="btn btn-light" type="submit"><?= htmlspecialchars($t('common.apply'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-modal>
        <div class="modal">
            <p><?= htmlspecialchars($deleteConfirmText, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-cancel><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button class="btn btn-primary" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-confirm><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
