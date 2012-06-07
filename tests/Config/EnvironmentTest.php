<?php
use MP\Config\Config;
use MP\Config\ConfigEnvironment;

$root = dirname(dirname(dirname(__FILE__))) . '/src/MP/Config';
require_once($root . "/ConfigEnvironment.php");
require_once($root . "/ConfigException.php");

class ConfigEnvironmentTest extends PHPUnit_Framework_TestCase
{
    public function testGetLineageWithSimpleName()
    {
        $env = new ConfigEnvironment();
        $actual = $env->getLineage('dev');
        $expected = array('dev');
        $this->assertEquals($expected, $actual);
    }

    public function testGetLineageWithCompositeName()
    {
        $env = new ConfigEnvironment();
        $actual = $env->getLineage('dev.foo');
        $expected = array('dev', 'dev.foo');
        $this->assertEquals($expected, $actual);
    }

    public function testJoinWithSimpleName()
    {
        $env = new ConfigEnvironment();
        $actual = $env->getLineage('dev', array('join' => '/'));
        $expected = array('dev');
        $this->assertEquals($expected, $actual);
    }

    public function testJoinWithCompositeName()
    {
        $env = new ConfigEnvironment();
        $actual = $env->getLineage('dev.foo', array('join' => '/'));
        $expected = array('dev', 'dev/foo');
        $this->assertEquals($expected, $actual);
    }

    public function testPrefixWithSimpleName()
    {
        $env = new ConfigEnvironment();
        $actual = $env->getLineage('dev', array(
                    'join'   => '/',
                    'prefix' => '/path/to/'));
        $expected = array(
                '/path/to/dev');
        $this->assertEquals($expected, $actual);
    }

    public function testPrefixWithCompositeName()
    {
        $env = new ConfigEnvironment();
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
        $env = new ConfigEnvironment();
        $actual = $env->getLineage('dev.mariano', array('root' => 'default'));
        $expected = array('default', 'dev', 'dev.mariano');
        $this->assertEquals($expected, $actual);
    }

    public function testSuffix()
    {
        $env = new ConfigEnvironment();
        $actual = $env->getLineage('dev.mariano', array('suffix' => '.ini'));
        $expected = array('dev.ini', 'dev.mariano.ini');
        $this->assertEquals($expected, $actual);
    }

    public function testAllOptions()
    {
        $env = new ConfigEnvironment();
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
        $env = new ConfigEnvironment();
        $this->setExpectedException('MP\Config\ConfigException');
        $env->getLineage('dev.mariano', array('invalid' => 'foo'));
    }
}
