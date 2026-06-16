<?php

declare(strict_types=1);

namespace AwesomePlugin\Http\Routes;

use WP_REST_Request;
use WpToolKit\Attribute\Route;
use WpToolKit\Controller\RouteController;

#[Route(
    'awesome-plugin/v1',
    '/ping',
    params: [],
    override: false,
    methods: 'GET'
)]
final class PingRoute extends RouteController
{
    public function callback(WP_REST_Request $request): mixed
    {
        return [
            'success' => true,
        ];
    }

    public function checkPermission(WP_REST_Request $request): bool
    {
        return true;
    }
}
