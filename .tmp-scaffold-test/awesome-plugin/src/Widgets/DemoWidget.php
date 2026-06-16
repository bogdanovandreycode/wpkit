<?php

declare(strict_types=1);

namespace AwesomePlugin\Widgets;

use WpToolKit\Attribute\Widget;
use WpToolKit\Controller\WidgetsController;

#[Widget('demo_widget', 'Demo Widget', 'Simple demo widget')]
final class DemoWidget extends WidgetsController
{
    public function widget($args, $instance): void
    {
        echo $args['before_widget'] ?? '';
        echo '<p>Widget output</p>';
        echo $args['after_widget'] ?? '';
    }

    public function form($instance): void
    {
        echo '<p>No settings yet.</p>';
    }

    public function update($new_instance, $old_instance): array
    {
        return $old_instance;
    }
}
