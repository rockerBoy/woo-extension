<?php

declare(strict_types = 1);

use Auryn\Injector;

$injector = new Injector;

$injector->alias(
    'Symfony\Component\HttpFoundation\Request',
    'Http\HttpRequest'
);
$injector->share('Http\HttpRequest');

return $injector;