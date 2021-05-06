<?php
/**
 * A WebFramework inspired by Symfony.
 * Development started on 10/2/2021
 *
 * @author Brian Monteagudo Perez <mbrianp05@gmail.com>
 */

use Mbrianp\FuncCollection\Http\Request;
use Mbrianp\FuncCollection\Kernel\Kernel;

if (version_compare(PHP_VERSION, '8.0', '<')) {
    throw new RuntimeException('PHP 8 version is required.');
}

define("BASE_DIR", dirname(__DIR__));

require_once BASE_DIR . '/vendor/autoload.php';

$config = parse_ini_file(BASE_DIR . '/.ini');

// Configuring folders
$config['root'] = BASE_DIR;
$config['templates_dir'] = $config['root'] . '\\' . $config['templates_dir'];

$kernel = new Kernel($config);
$kernel->deployApp(Request::createFromGlobals());