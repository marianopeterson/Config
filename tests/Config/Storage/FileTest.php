<?php
use MP\Config\Config;
use MP\Config\Storage\FileStorage;

$root = dirname(dirname(dirname(dirname(__FILE__)))) . '/src/MP/Config';
require_once($root . "/Config.php");
require_once($root . "/ConfigException.php");
require_once($root . "/Storage/StorageInterface.php");
require_once($root . "/Storage/FileStorage.php");

class FileStorageTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorWithDefaultOptions()
    {
        $storage = new FileStorage();
        $this->assertInstanceOf('MP\Config\Storage\StorageInterface', $storage);
    }

    public function testConstructorWithInvalidOption()
    {
        $this->setExpectedException('MP\Config\ConfigException');
        $storage = new FileStorage(array('invalid' => 'foo'));
    }

    public function testConstructorWithValidOption()
    {
        $storage = new FileStorage(array('root' => 'foo'));
        $this->assertTrue(true);
    }

    public function testSetAndGet()
    {
        $root = '/tmp/';
        $key  = 'phpunit-' . __CLASS__ . '-' . __FUNCTION__ . '-' . time();
        if (!is_writable($root)) {
            $this->fail('Unable to write to tmp file: ' .  $key);
        }
        $storage  = new FileStorage(array('root' => $root));
        $contents = __METHOD__;
        $storage->set($key, $contents);
        $actual = $storage->get($key);
        $this->assertEquals($contents, $actual);
    }

    public function testSetWithErrorFileDoesNotExist()
    {
        $root = '/tmp/dir/does/not/exist/';
        $key = 'phpunit-' . __CLASS__ . '-' . __FUNCTION__ . '-' . time();
        $storage   = new FileStorage(array('root' => $root));
        $contents  = '229dss9999';
        $setResult = $storage->set($key, $contents);
        $this->assertFalse($setResult);
    }

    public function testGetWithError()
    {
        $root = '/tmp/dir/does/not/exist/';
        $key  = 'phpunit-' . __CLASS__ . '-' . __FUNCTION__ . '-' . time();
        $storage = new FileStorage(array('root' => $root));
        $storage->get($key);
        $actual = $storage->get($key);
        $this->assertFalse($actual);
    }
}
