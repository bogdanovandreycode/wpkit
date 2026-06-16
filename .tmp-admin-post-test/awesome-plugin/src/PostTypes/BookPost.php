<?php

declare(strict_types=1);

namespace AwesomePlugin\PostTypes;

use WpToolKit\Controller\PostController;
use WpToolKit\Entity\Post;

final class BookPost extends PostController
{
    public function __construct()
    {
        parent::__construct(
            new Post(
                'book',
                'Books',
                'dashicons-book',
                'manage_options',
                ['title', 'editor', 'thumbnail'],
                public: true,
                rest: true,
                position: 20,
            )
        );

        // Call $this->addToMenu() here if you want a dedicated top-level menu item.
    }

    public function renderContent(mixed $content): mixed
    {
        return $content;
    }
}
