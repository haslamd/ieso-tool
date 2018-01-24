<?php
/*
 * This file is part of ieso-tool - the ieso query and download tool.
 *
 * (c) LivITy Consultinbg Ltd, Enbridge Inc., Kevin Edwards
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
use LivITy\IESO\Crawler;;
use LivITy\IESO\Logger;
use LivITy\IESO\Config;

if (! file_exists($composer = dirname(__FILE__) . '/vendor/autoload.php')) {
    throw new RuntimeException("Please run 'composer install' first to set up autoloading. $composer");
}

/** @var \Composer\Autoload\ClassLoader $autoloader */
$autoloader = include $composer;

$root = __DIR__ . '\\src\\';
$config = new Config($root);
$logger = new Logger($config);
$crawler = new Crawler($config, $logger);

$logger->get_logger()->info(' ===== Starting Recurse on ' .  date(DATE_RFC2822) . ' =====');

$stats_template = dirname(__FILE__) . '/src/views/stats_' . date('Y-m-d') . '.txt';
file_exists($stats_template) ? unlink($stats_template) : null;
$paths = null; //['TRA-Results/', 'TRA-BidHistory/'];

if (is_array($paths)) {
    foreach ($paths as $path) {
            $stats = $crawler->recurse(\Env::get('IESO_ROOT_PATH') . $path);
            $m = new Mustache_Engine([
                'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/src/views/')
            ]);
            file_put_contents(dirname(__FILE__) . '/src/views/stats_' . date('Y-m-d') . '.txt', $m->render('stats', $stats), FILE_APPEND | LOCK_EX);
    }
} else {
    $stats = $crawler->recurse(\Env::get('IESO_ROOT_PATH'));
    $m = new Mustache_Engine([
        'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/src/views/')
    ]);
    file_put_contents(dirname(__FILE__) . '/src/views/stats_' . date('Y-m-d') . '.txt', $m->render('stats', $stats), FILE_APPEND | LOCK_EX);
}
$logger->get_logger()->info(' ===== Completed Recurse on ' .  date(DATE_RFC2822) . ' =====');


exit(0);