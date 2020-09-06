<?php

use ExtendedWoo\Kernel;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/../vendor/autoload.php';

$loader = new FilesystemLoader(__DIR__.'/views');
$twig = new Environment($loader);
$app = (new Kernel($twig))->init();
