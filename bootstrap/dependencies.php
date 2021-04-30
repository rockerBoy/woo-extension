<?php

$files = array_merge(
    glob(__DIR__.'/../config/dependencies/*.php' ?: []),
    glob(__DIR__.'/../config/*.php' ?: []),
);
$array_map = [];

foreach ($files as $key => $file) {
    $array_map[$key] = require $file;
}

$config = $array_map;

return array_merge_recursive(...$config);