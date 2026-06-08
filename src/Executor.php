<?php

namespace framework\web;

use framework\web\exceptions\NotFoundException;
use framework\web\routing\Router;

/**
 * This class executes the HTTP request by taking all required values and running them together as required.
 */
class Executor
{
    public function __construct(public Router $router)
    {
    }

    public function execute(string $route, string $method)
    {
        $route = $this->router->resolve($route, $method);

        if (empty($route)) {
            throw new NotFoundException();
        }

        $result = $route['route']->execute($route['params'], app());

        if (!empty($result)) {
            if (\is_string($result)) {
                echo $result;
            } else {
                $result->render();
            }
        }
    }
}