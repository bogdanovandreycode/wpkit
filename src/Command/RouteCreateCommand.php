<?php

declare(strict_types=1);

namespace Wpkit\Command;

use Wpkit\Controller\ScaffoldCatalog;

final class RouteCreateCommand extends ScaffoldMakeCommand
{
    public function __construct()
    {
        parent::__construct(ScaffoldCatalog::routeCreate());
    }
}
