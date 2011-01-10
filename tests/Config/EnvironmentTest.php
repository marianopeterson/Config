<?php
$root = dirname(dirname(dirname(__FILE__)));
require_once($root . "/Config/Environment.php");
require_once($root . "/Config/Exception.php");

class Config_EnvironmentTest extends PHPUnit_Framework_TestCase
{
    public function testGetLineageWithSimpleName()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev');
        $expected = array('dev');
        $this->assertEquals($expected, $actual);
    }

    public function testGetLineageWithCompositeName()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev.foo');
        $expected = array('dev', 'dev.foo');
        $this->assertEquals($expected, $actual);
    }

    public function testJoinWithSimpleName()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev', array('join' => '/'));
        $expected = array('dev');
        $this->assertEquals($expected, $actual);
    }

    public function testJoinWithCompositeName()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev.foo', array('join' => '/'));
        $expected = array('dev', 'dev/foo');
        $this->assertEquals($expected, $actual);
    }

    public function testPrefixWithSimpleName()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev', array(
                    'join'   => '/',
                    'prefix' => '/path/to/'));
        $expected = array(
                '/path/to/dev');
        $this->assertEquals($expected, $actual);
    }

    public function testPrefixWithCompositeName()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev.mariano', array(
                    'join'   => '.',
                    'prefix' => '/path/to/'));
        $expected = array(
                '/path/to/dev',
                '/path/to/dev.mariano');
        $this->assertEquals($expected, $actual);
    }

    public function testRoot()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev.mariano', array('root' => 'default'));
        $expected = array('default', 'dev', 'dev.mariano');
        $this->assertEquals($expected, $actual);
    }

    public function testSuffix()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev.mariano', array('suffix' => '.ini'));
        $expected = array('dev.ini', 'dev.mariano.ini');
        $this->assertEquals($expected, $actual);
    }

    public function testAllOptions()
    {
        $env = new Config_Environment();
        $actual = $env->getLineage('dev.mariano', array(
                    'root'   => 'default',
                    'prefix' => '/path/to/',
                    'join'   => '.',
                    'suffix' => '.ini'));
        $expected = array(
                '/path/to/default.ini',
                '/path/to/dev.ini',
                '/path/to/dev.mariano.ini');
        $this->assertEquals($expected, $actual);
    }

    public function testInvalidOption()
    {
        $env = new Config_Environment();
        $this->setExpectedException('Config_Exception');
        $env->getLineage('dev.mariano', array('invalid' => 'foo'));
    }
}
