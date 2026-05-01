<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Support\Avatar as AvatarGenerator;

final class Avatar
{
    public function __construct(private AvatarGenerator $avatars)
    {
    }

    public function show(array $params): void
    {
        $seed = rawurldecode(trim((string)($params['seed'] ?? '')));
        $size = (int)($_GET['size'] ?? 128);
        $svg = $this->avatars->svg($seed, $size);
        $etag = '"' . sha1($svg) . '"';

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);

        if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
            http_response_code(304);
            return;
        }

        echo $svg;
    }
}
