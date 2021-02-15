<?php

use Mbrianp\FuncCollection\Http\Request;
use Mbrianp\FuncCollection\Kernel\Kernel;
use Mbrianp\FuncCollection\Routing\ClassMap;

if (version_compare(PHP_VERSION, '8.0', '<')) {
    throw new RuntimeException('PHP 8 version is required.');
}

require_once 'Autoloader.php';

$autoloader = new Autoloader([
    'Mbrianp\FuncCollection' => 'classes',
    'App\Controller' => 'src/Controller',
    'App\Entity' => 'src/Entity',
    'App\Repository' => 'src/Repository',
]);
$autoloader->run();

require_once 'map.php';

$config = parse_ini_file(__DIR__ . '/.ini');

$classes = ClassMap::$classes;
$kernel = new Kernel($config, $classes);

$kernel->deployApp(Request::createFromGlobals());