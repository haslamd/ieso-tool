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

use LivITy\IESO\Logger,
    LivITy\IESO\Config;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private $logger;
    private $file;

    public function setUp()
    {
        $root = dirname(__DIR__, 4) . '\\tests\\';
        $config = new Config($root);
        $this->logger = new Logger($config, 'ieso_test');
        $this->file = realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/'. 'ieso_test.log';
    }

    /** @test */
    public function testLoggerIsInitialized()
    {
        $classInit = class_exists('LivITy\IESO\Logger');
        $this->assertEquals(true, $classInit);
        $this->assertEquals('LivITy\IESO\Logger', get_class($this->logger));
    }

    /** @test */
    public function testLocalLogFileIsCreated()
    {
        $text = 'test logging';
        $this->logger->get_logger()->info($text);
        $this->assertFileExists($this->file);
    }

    /** @test */
    public function testLocalFileContent()
    {
        $text = 'missing you today!';
        $this->logger->get_logger()->info($text);
        $this->assertContains($text, file_get_contents($this->file));
    }

    /** @test */
	    public function testFileSize()
	    {
	       $logger = $this->logger->get_logger();
	       $handlers = $logger->getHandlers();
	       $url = $handlers[0]->getUrl();

	       $filesize = 0;
	       if (file_exists($file = \Env::get('IESO_ENBRIDGE_PATH') . '/ieso.log')) {
	           $filesize = filesize($file);
	           if ($filesize > $this->logger->stringSizeToBytes('10 MB')) {
	               unlink($file);
	               $filesize = 0;
	           }
	       }
	       $this->assertLessThan(10485760, $filesize);
   }

}
