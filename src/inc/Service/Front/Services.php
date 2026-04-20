<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Application\Comment;
use App\Service\Application\Content;
use App\Service\Application\Media;
use App\Service\Application\Settings;
use App\Service\Application\Term;
use App\Service\Application\User;

final class Services
{
    public function __construct(
        public readonly Content $content,
        public readonly Comment $comment,
        public readonly User $user,
        public readonly Media $media,
        public readonly Term $term,
        public readonly Settings $settings,
    ) {
    }
}
