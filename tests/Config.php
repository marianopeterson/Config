<?php
$root = dirname(dirname(__FILE__));
require_once($root . "/Config.php");
require_once($root . "/Config/Exception.php");

class Test_Config extends PHPUnit_Framework_TestCase
{
    public function testGetInstanceReturnsSameObject()
    {
        $a = Config::getInstance();
        $b = Config::getInstance();
        $this->assertSame($a, $b);
    }

    public function testLoadWithSingleStorage()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('Config_Storage_File', array('fetch'));
        $storage->expects($this->any())
                ->method('fetch')
                ->will($this->returnValue($configData));

        $config = $this->getMock('Config', array('parseSpec'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));

        $actual = $config->load($storage);
        $this->assertEquals($configData, $actual);
    }

    public function testLoadWithMultipleStorage()
    {
        $configData1 = array(
            "db.host" => "10.0.0.1",
            "db.port" => 3306);

        $configData2 = array(
            "db.port" => 4000,
            "db.name" => "testdb");

        $storage1 = $this->getMock('Config_Storage_File', array('fetch'));
        $storage1->expects($this->any())
                 ->method('fetch')
                 ->will($this->returnValue($configData1));

        $storage2 = $this->getMock('Config_Storage_File', array('fetch'));
        $storage2->expects($this->any())
                 ->method('fetch')
                 ->will($this->returnValue($configData2));
        
        $config = $this->getMock('Config', array('parseSpec'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));

        $actual = $config->load(array($storage1, $storage2));
        $expected = array(
            "db.host" => "10.0.0.1",
            "db.port" => "4000",
            "db.name" => "testdb");
        $this->assertEquals($expected, $actual);
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
}
