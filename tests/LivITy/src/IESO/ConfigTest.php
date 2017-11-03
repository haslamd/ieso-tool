<?php
/*
 * This file is part of ieso-tool - the ieso query and download tool.
 *
 * (c) LivITy Consultinbg Ltd, Enbridge Inc., Kevin Edwards
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
declare(strict_types=1);

use LivITy\IESO\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected $config;

    public function setUp()
    {
        $root = dirname(__DIR__, 4) . '\\tests\\';
        $this->config = new Config($root);
    }

    /** @test */
    public function testConfigIsInitialized()
    {
        $classInit = class_exists('LivITy\IESO\Config');
        $this->assertEquals(true, $classInit);
        $this->assertEquals('LivITy\IESO\Config', get_class($this->config));
        //$this->assertEquals(\Env::get('IESO_APP_ENV'), 'dev');
    }
}
