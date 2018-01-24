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
use LivITy\IESO\Crawler;
use LivITy\IESO\Logger;
use PHPUnit\Framework\TestCase;

class CrawlerTest extends TestCase
{
    protected $crawler;

    public function setUp()
    {
        $root = dirname(__DIR__, 4) . '\\tests\\';
        $config = new Config($root);
        $logger = new Logger($config, 'ieso_test');
        $this->crawler = new Crawler($config, $logger);
    }

    protected function delete_files($target)
    {
        $it = new \RecursiveDirectoryIterator($target, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
    }

    /** @test */
    public function testCrawlerIsInitialized()
    {
        $this->assertEquals('LivITy\IESO\Crawler', get_class($this->crawler));
    }

    /** @test */
    public function testCrawlerClientIsGuzzle()
    {
        $this->assertEquals('GuzzleHttp\Client', get_class($this->crawler->client));
    }

    /** @test */
    public function testCrawlerDirIsRetrieved()
    {
        $data = $this->crawler->request(\Env::get('IESO_ROOT_PATH'), 1);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('DT-P-F', $data);
        $this->assertEquals(true, $data['DT-P-F']['isDirectory']);
    }

    /** @test */
    public function testCrawlerFileIsRetrieved()
    {
        $data = $this->crawler->request(\Env::get('IESO_ROOT_PATH') . 'DT-P-F', 0, 1);
        $this->assertCount(1, $data);
        $file = current($data);
        $this->assertEquals(true, $file['isRegularFile']);
    }

    /** @test */
    public function testCrawlerFileCountIsRetrieved()
    {
        $fileCount = rand(1, 9);
        $data = $this->crawler->request(\Env::get('IESO_ROOT_PATH') . 'DT-P-F', 0, $fileCount);
        $this->assertCount($fileCount, $data);
    }

    /** @test */
    public function testCrawlerFileWrittenToStorage()
    {
        $this->delete_files(realpath(\Env::get('IESO_ENBRIDGE_PATH')));

        $fileCount = 1;
        $data = $this->crawler->request(\Env::get('IESO_ROOT_PATH') . 'DT-P-F', 0, $fileCount, true);
        foreach($data as $k => $v) {
            $fi = new \FilesystemIterator($v['storage'], \FilesystemIterator::SKIP_DOTS);
            $this->assertEquals($fileCount, iterator_count($fi));
        }
    }

    /** @test */
    public function testCrawlerMultipleFilesWrittenToStorage()
    {
        $this->delete_files(realpath(\Env::get('IESO_ENBRIDGE_PATH')));

        $fileCount = 4;
        $data = $this->crawler->request(\Env::get('IESO_ROOT_PATH') . 'DT-P-F', 0, $fileCount, true);
        foreach($data as $k => $v) {
            $fi = new \FilesystemIterator($v['storage'], \FilesystemIterator::SKIP_DOTS);
            $this->assertEquals($fileCount, iterator_count($fi));
        }
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function testCrawlerException()
    {
        $this->crawler->request(\Env::get('IESO_ROOT_PATH') . 'FAKE-PATH');
    }

    /** @test */
    public function testFiletime()
    {
        $fileCount = 5;
        $data = $this->crawler->request(\Env::get('IESO_ROOT_PATH') . 'DT-P-F', 0, $fileCount, true);
        foreach($data as $k => $v) {
            $fi = new \FilesystemIterator($v['storage'], \FilesystemIterator::SKIP_DOTS);
            $this->assertEquals($fileCount, iterator_count($fi));
        }
    }

    /** @test */
    public function testDirtime()
    {
        $data = $this->crawler->getData([
            \Env::get('IESO_ROOT_PATH') . 'TRA-Results/',
        ]);
        $this->assertTrue($data);
    }
}
