<?php
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root . "/Config.php");
require_once($root . "/Config/Exception.php");
require_once($root . "/Config/Storage/Interface.php");
require_once($root . "/Config/Storage/File.php");

class Config_Storage_FileTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorWithDefaultOptions()
    {
        $storage = new Config_Storage_File();
        $this->assertType('Config_Storage_Interface', $storage);
    }

    public function testConstructorWithInvalidOption()
    {
        $this->setExpectedException('Config_Exception');
        $storage = new Config_Storage_File(array('invalid' => 'foo'));
    }

    public function testConstructorWithValidOption()
    {
        $storage = new Config_Storage_File(array('root' => 'foo'));
        $this->assertTrue(true);
    }

    public function testSet()
    {
        $root = '/tmp/';
        $key  = 'phpunit-' . __CLASS__ . '-' . __FUNCTION__ 
            . '-' . time();
        if (!is_writable($root)) {
            $this->fail('Unable to write to tmp file: ' .  $key);
        }
        $storage  = new Config_Storage_File(array('root' => $root));
        $contents = '229dss9999';
        $storage->set($key, $contents);
        $actual = $storage->get($key);
        $this->assertEquals($contents, $actual);
    }

    public function testSetWithError()
    {
        $root = '/tmp/dir/does/not/exist/';
        $key  = 'phpunit-' . __CLASS__ . '-' . __FUNCTION__ 
            . '-' . time();
        $storage   = new Config_Storage_File(array('root' => $root));
        $contents  = '229dss9999';
        $setResult = $storage->set($key, $contents);
        $this->assertFalse($setResult);
    }

    public function testGetWithError()
    {
        $root = '/tmp/dir/does/not/exist/';
        $key  = 'phpunit-' . __CLASS__ . '-' . __FUNCTION__ 
            . '-' . time();
        $storage = new Config_Storage_File(array('root' => $root));
        $storage->get($key);
        $actual = $storage->get($key);
        $this->assertFalse($actual);
    }

}
