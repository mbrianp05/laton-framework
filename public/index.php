<?php
/**
 * A WebFramework inspired on Symfony.
 * Development started on 10/2/2021
 *
 * @author Brian Monteagudo Perez <mbrianp05@gmail.com>
 */

use Mbrianp\FuncCollection\Http\Request;
use Mbrianp\FuncCollection\Kernel\Kernel;
use Mbrianp\FuncCollection\Routing\ClassMap;

if (version_compare(PHP_VERSION, '8.0', '<')) {
    throw new RuntimeException('PHP 8 version is required.');
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once 'map.php';

$config = parse_ini_file(__DIR__ . '/.ini');

$classes = ClassMap::$classes;
$kernel = new Kernel($config, $classes);

$kernel->deployApp(Request::createFromGlobals());