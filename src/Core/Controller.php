<?php declare(strict_types = 1);

namespace TheSaiged\Core;

use Closure;
use RuntimeException;
use Throwable;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;

trait Controller {

    /**
     * Returns a route handler that resolves this controller from the container,
     * calls the named method with the Request, and routes any thrown exception
     * to the controller's onError.
     *
     * @return Closure(Request):Response
     */
    static function handler (string $method): Closure {
        $class = static::class;
        return function (Request $request) use ($class, $method): Response {
            $instance = Container::get($class);
            try {
                /** @phpstan-ignore method.dynamicName */
                $response = $instance->$method($request);
                if (!$response instanceof Response)
                    throw new RuntimeException("$class::$method must return " . Response::class);
                return $response;
            } catch (Throwable $exception) {
                return $instance->onError($exception, $request);
            }
        };
    }

    abstract function onError (Throwable $exception, Request $request): Response;

}
