<?php

declare(strict_types=1);

namespace AwesomePlugin\Hooks;

use WpToolKit\Attribute\Action;
use WpToolKit\Controller\ActionController;

#[Action('init', priority: 10, acceptedArgs: 1)]
final class RegisterSomethingAction extends ActionController
{
    public function handle(...$args): void
    {
        // Put action logic here.
    }
}
