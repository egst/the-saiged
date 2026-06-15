<?php declare(strict_types = 1);

namespace TheSaiged\Core\Http;

use TheSaiged\Core\Http\Exception\NotFoundException;

final readonly class Router {

    /** @param list<Route> $routes */
    function __construct (
        private array $routes,
    ) {}

    function dispatch (Request $request): Response {
        foreach ($this->routes as $route) {
            $response = $route->try($request);
            if ($response !== null)
                return $response;
        }
        throw new NotFoundException();
    }

}
