<?php
$root = dirname(dirname(__FILE__));
require_once($root . "/Config.php");
require_once($root . "/Config/Exception.php");
require_once($root . "/Config/Storage/Interface.php");
require_once($root . "/Config/Storage/File.php");

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testGetInstanceReturnsSameObject()
    {
        $a = Config::getInstance();
        $b = Config::getInstance();
        $this->assertSame($a, $b);
    }

    public function testLoadWithSource()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('Config_Storage_File', array('get'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue($configData));

        $config = $this->getMock('Config', array('parseSpec', 'getSource'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSource')
               ->will($this->returnValue($storage));

        $actual = $config->load('foo')->toArray();
        $this->assertEquals($configData, $actual);
    }

    public function testLoadWithoutSource()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('Config_Storage_File', array('get'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue($configData));

        $config = $this->getMock('Config', array('parseSpec', 'getSource'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSource')
               ->will($this->returnValue(null));

        $this->setExpectedException('Config_Exception');
        $actual = $config->load('foo')->toArray();
    }

    public function testLoadWithWarmCache()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('Config_Storage_File', array('get', 'set'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue(serialize($configData)));
        $storage->expects($this->any())
                ->method('set')
                ->will($this->returnValue(true));

        $config = $this->getMock('Config', array('parseSpec', 'getCache'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getCache')
               ->will($this->returnValue($storage));

        $actual = $config->load('foo')->toArray();
        $this->assertEquals($configData, $actual);
    }

    public function testLoadWithColdCache()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $cache = $this->getMock('Config_Storage_File', array('get', 'set'));
        $cache->expects($this->any())
              ->method('get')
              ->will($this->returnValue(false));
        $cache->expects($this->any())
              ->method('set')
              ->will($this->returnValue(true));

        $source = $this->getMock('Config_Storage_File', array('get'));
        $source->expects($this->any())
               ->method('get')
               ->will($this->returnValue($configData));

        $config = $this->getMock('Config', array('parseSpec', 'getSource', 'getCache'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSource')
               ->will($this->returnValue($source));
        $config->expects($this->any())
               ->method('getCache')
               ->will($this->returnValue($cache));

        $actual = $config->load('foo')->toArray();
        $this->assertEquals($configData, $actual);
    }
    public function testGet()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('Config_Storage_File', array('get'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue($configData));

        $config = $this->getMock('Config', array('parseSpec', 'getSource'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSource')
               ->will($this->returnValue($storage));

        $config->load('foo');
        $this->assertEquals('3306', $config->get('db.port'));
    }

    public function testGetWithInvalidKey()
    {
        $config = new Config();
        $this->setExpectedException('Config_Exception');
        $config->get("invalid.key");
    }

    public function testParseSpec()
    {
        $spec = <<<EOT
            db.host = 10.0.0.1
            db.port = 3306
EOT;
        $config = new Config();
        $actual = $config->parseSpec($spec);
        $expected = array(
            "db.host" => "10.0.0.1",
            "db.port" => 3306);
        $this->assertEquals($expected, $actual);
    }

    public function testParseSpecWithEmtpyLines()
    {
        $spec = "    db.host = 10.0.0.1\n"
              . "\tdb.port = 3306";
        $config = new Config();
        $actual = $config->parseSpec($spec);
        $expected = array(
            "db.host" => "10.0.0.1",
            "db.port" => 3306);
        $this->assertEquals($expected, $actual);
    }

    public function testParseSpecWithCommentLines()
    {
        $spec = <<<EOT
            #comment1
            db.host = 10.0.0.1
            #comment2 = foo
            //other comment
            db.port = 3306
            # comment3
EOT;
        $config = new Config();
        $actual = $config->parseSpec($spec);
        $expected = array(
            "db.host" => "10.0.0.1",
            "db.port" => 3306);
        $this->assertEquals($expected, $actual);
    }

    public function testParseSpecWithTypes()
    {
        $spec = <<<EOT
            db.host,string[] = 10.0.0.1, 10.0.0.2
            db.port,int = 3306
            db.name = testdb
EOT;
        $config = new Config();
        $actual = $config->parseSpec($spec);
        $expected = array(
            "db.host" => array("10.0.0.1","10.0.0.2"),
            "db.port" => 3306,
            "db.name" => "testdb");
        $this->assertEquals($expected, $actual);
    }

    public function testParseSpecWithMissingKeyDelimiter()
    {
        // Note that the first empty line has a tab character
        $spec = <<<EOT
            db.host 10.0.0.1
EOT;
        $this->setExpectedException('Config_Exception');
        $config = new Config();
        $actual = $config->parseSpec($spec);
    }

    public function testSplitStringWithNoSplit()
    {
        $config = new Config();
        $actual = $config->splitString('foo');
        $this->assertEquals(array('foo'), $actual);
    }

    public function testSplitStringWithSplit()
    {
        $config = new Config();
        $actual = $config->splitString('foo,bar,bat');
        $this->assertEquals(array('foo', 'bar', 'bat'), $actual);
    }

    public function testSplitStringWithEscapedDelimiter()
    {
        $config = new Config();
        $actual = $config->splitString("foo\,bar,bat");
        $expected = array("foo,bar", "bat");
        $this->assertEquals($expected, $actual);
    }

    public function testSplitStringWithCustomDelimiter()
    {
        $config = new Config();
        $actual = $config->splitString('foo:bar', ':');
        $expected = array('foo', 'bar');
        $this->assertEquals($expected, $actual);
    }

    public function testSplitStringWithMetaChars()
    {
        $config = new Config();
        $actual = $config->splitString('foo|bar', '|');
        $expected = array('foo', 'bar');
        $this->assertEquals($expected, $actual);
    }

    public function testSplitStringWithBackslash()
    {
        $config = new Config();
        $actual = $config->splitString('foo\bar', '\\');
        $expected = array('foo', 'bar');
        $this->assertEquals($expected, $actual);
    }

    public function testCastToBoolWithInt()
    {
        $config = new Config();
        $actual = $config->cast('1', 'bool');
        $this->assertEquals(true, $actual);
        $this->assertInternalType('bool', $actual);
    }

    public function testCastToBoolWithTruthyStrings()
    {
        $config = new Config();

        $actual = $config->cast('true', 'bool');
        $this->assertEquals(true, $actual);
        $this->assertInternalType('bool', $actual);

        $actual = $config->cast('yes', 'bool');
        $this->assertEquals(true, $actual);
        $this->assertInternalType('bool', $actual);

        $actual = $config->cast('on', 'bool');
        $this->assertEquals(true, $actual);
        $this->assertInternalType('bool', $actual);

        $actual = $config->cast('enabled', 'bool');
        $this->assertEquals(true, $actual);
        $this->assertInternalType('bool', $actual);
    }

    public function testCastToBoolWithFalsyStrings()
    {
        $config = new Config();

        $actual = $config->cast('false', 'bool');
        $this->assertEquals(false, $actual);
        $this->assertInternalType('bool', $actual);

        $actual = $config->cast('no', 'bool');
        $this->assertEquals(false, $actual);
        $this->assertInternalType('bool', $actual);

        $actual = $config->cast('off', 'bool');
        $this->assertEquals(false, $actual);
        $this->assertInternalType('bool', $actual);

        $actual = $config->cast('disabled', 'bool');
        $this->assertEquals(false, $actual);
        $this->assertInternalType('bool', $actual);
    }

    public function testCastWithInt()
    {
        $config = new Config();
        $actual = $config->cast('1', 'int');
        $this->assertEquals(1, $actual);
        $this->assertInternalType('int', $actual);
    }

    public function testCastWithFloat()
    {
        $config = new Config();
        $actual = $config->cast('1', 'float');
        $this->assertEquals(1.0, $actual);
        $this->assertInternalType('float', $actual);
    }

    public function testCastWithString()
    {
        $config = new Config();
        $actual = $config->cast('foo', 'string');
        $this->assertEquals('foo', $actual);
        $this->assertInternalType('string', $actual);
    }

    public function testCastWithIntArray()
    {
        $config = new Config();
        $actual = $config->cast('1, 2 , 3', 'int[]');
        $expected = array(1,2,3);
        $this->assertEquals($expected, $actual);
        foreach ($actual as $int) {
            $this->assertInternalType('int', $int);
        }
    }

    public function testSourceAccessors()
    {
        $config    = new Config();
        $storage   = new Config_Storage_File(array('root' => '/tmp/foo'));
        $setResult = $config->setSource($storage);

        $this->assertType('Config', $setResult);
        $this->assertEquals($config->getSource(), $storage);
    }

    public function testCacheAccessors()
    {
        $config    = new Config();
        $storage   = new Config_Storage_File(array('root' => '/tmp/foo'));
        $setResult = $config->setCache($storage);

        $this->assertType('Config', $setResult);
        $this->assertEquals($config->getCache(), $storage);
    }
}
