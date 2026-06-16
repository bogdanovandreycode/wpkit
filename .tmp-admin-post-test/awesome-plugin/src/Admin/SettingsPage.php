<?php

declare(strict_types=1);

namespace AwesomePlugin\Admin;

use WpToolKit\Attribute\Page;
use WpToolKit\Controller\AdminPage;

#[Page(
    'Demo Settings',
    'Demo Settings',
    'manage_options',
    'demo-settings',
    25,
    isSubManuItem: false,
    parentUrl: null,
    icon: null
)]
final class SettingsPage extends AdminPage
{
    public function render(): void
    {
        echo '<div class="wrap"><h1>' . esc_html('Demo Settings') . '</h1></div>';
    }

    public function callback(): void
    {
        // Handle admin POST logic here.
    }
}
