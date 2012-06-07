<?php
use MP\Config\Config;
use MP\Config\Storage;

$root = dirname(dirname(__FILE__)) . '/src/MP/Config';
require_once($root . "/Config.php");
require_once($root . "/ConfigException.php");
require_once($root . "/Storage/StorageInterface.php");
require_once($root . "/Storage/FileStorage.php");

class ConfigTest extends \PHPUnit_Framework_TestCase
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

        $storage = $this->getMock('MP\Config\Storage\FileStorage', array('get'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue($configData));

        $config = $this->getMock('MP\Config\Config', array('parseSpec', 'getSourceEngine'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSourceEngine')
               ->will($this->returnValue($storage));

        $actual = $config->load('foo')->toArray();
        $this->assertEquals($configData, $actual);
    }

    public function testLoadWithoutSource()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('MP\Config\Storage\FileStorage', array('get'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue($configData));

        $config = $this->getMock('MP\Config\Config', array('parseSpec', 'getSourceEngine'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSourceEngine')
               ->will($this->returnValue(null));

        $this->setExpectedException('MP\Config\ConfigException');
        $actual = $config->load('foo')->toArray();
    }

    public function testLoadWithColdCache()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $cache = $this->getMock('MP\Config\Config\FileStorage', array('get', 'set'));
        $cache->expects($this->any())
              ->method('get')
              ->will($this->returnValue(false));
        $cache->expects($this->any())
              ->method('set')
              ->will($this->returnValue(true));

        $source = $this->getMock('MP\Config\Config\FileStorage', array('get'));
        $source->expects($this->any())
               ->method('get')
               ->will($this->returnValue($configData));

        $config = $this->getMock('MP\Config\Config',
                array('parseSpec', 'getSourceEngine', 'getCacheEngines'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSourceEngine')
               ->will($this->returnValue($source));
        $config->expects($this->any())
               ->method('getCacheEngines')
               ->will($this->returnValue(array($cache)));

        $actual = $config->load('foo')->toArray();
        $this->assertEquals($configData, $actual);
    }

    public function testLoadWithWarmCache()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('MP\Config\Storage\FileStorage', array('get', 'set'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue(serialize($configData)));
        $storage->expects($this->any())
                ->method('set')
                ->will($this->returnValue(true));

        $config = $this->getMock('MP\Config\Config', array('parseSpec', 'getCacheEngines'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getCacheEngines')
               ->will($this->returnValue(array($storage)));

        $actual = $config->load('foo')->toArray();
        $this->assertEquals($configData, $actual);
    }

    /**
     * Prove that setting the reload flag in the load() method causes
     * us to access the source even when the cache has contnet.
     */
    public function testLoadWithWarmCacheAndReload()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $source = $this->getMock('MP\Config\Storage\FileStorage', array('get'));
        // We must get() from source:
        $source->expects($this->atLeastOnce())
               ->method('get')
               ->will($this->returnValue('any-string'));

        $cache = $this->getMock('MP\Config\Storage\FileStorage', array('get', 'set'));
        // We must not try to get() from cache:
        $cache->expects($this->never())
              ->method('get')
              ->will($this->returnValue(serialize($configData)));
        // We must set() the cache after getting from source:
        $cache->expects($this->atLeastOnce())
              ->method('set')
              ->will($this->returnValue(true));

        $config = $this->getMock('MP\Config\Config',
                array('getSourceEngine', 'getCacheEngines', 'parseSpec'));
        $config->expects($this->any())
               ->method('getSourceEngine')
               ->will($this->returnValue($source));
        $config->expects($this->any())
               ->method('getCacheEngines')
               ->will($this->returnValue(array($cache)));
        $config->expects($this->once())
               ->method('parseSpec')
               ->will($this->returnValue($configData));

        $actual = $config->load('foo', true)->toArray();
        $this->assertEquals($configData, $actual);
    }

    public function testGet()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        $storage = $this->getMock('MP\Config\Storage\FileStorage', array('get'));
        $storage->expects($this->any())
                ->method('get')
                ->will($this->returnValue($configData));

        $config = $this->getMock('MP\Config\Config', array('parseSpec', 'getSourceEngine'));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnArgument(0));
        $config->expects($this->any())
               ->method('getSourceEngine')
               ->will($this->returnValue($storage));

        $config->load('foo');
        $this->assertEquals('3306', $config->get('db.port'));
    }

    public function testGetWithInvalidKey()
    {
        $config = new Config();
        $this->setExpectedException('MP\Config\ConfigException');
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
        $this->setExpectedException('MP\Config\ConfigException');
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
        $storage   = new MP\Config\Storage\FileStorage(array('root' => '/tmp/foo'));
        $setResult = $config->setSourceEngine($storage);

        $this->assertInstanceOf('MP\Config\Config', $setResult);
        $this->assertEquals($config->getSourceEngine(), $storage);
    }

    public function testCacheAccessors()
    {
        $config    = new Config();
        $storage   = new Storage\FileStorage(array('root' => '/tmp/foo'));
        $setResult = $config->addCacheEngine($storage);

        $this->assertInstanceOf('MP\Config\Config', $setResult);
        $this->assertEquals($config->getCacheEngines(), array($storage));
    }

    public function testCacheWithMultipleEngines()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        // MySQL canonical data store; we should never have to call get() on
        // this because even though the APC cache will miss, the secondary
        // File cache should hit (in our test case below).
        $mysql = $this->getMock('MP\Config\Storage\MysqlStorage', array('get'));
        $mysql->expects($this->never())
              ->method('get')
              ->will($this->returnValue(''));

        // APC cache will miss; we must call get() atLeastOnce to see this.
        // We must also call set() atLeastOnce to prove we repopulated the
        // APC cache after the File cache.
        $apc = $this->getMock('MP\Config\Storage\ApcStorage', array('get', 'set'));
        $apc->expects($this->atLeastOnce())
            ->method('get')
            ->will($this->returnValue(false));
        $apc->expects($this->atLeastOnce())
            ->method('set')
            ->will($this->returnValue(true));

        // File cache will hit; we should call get() atLeastOnce
        // Since this cache hit, we should never call set() on it.
        $file = $this->getMock('MP\Config\Storage\FileStorage', array('get'));
        $file->expects($this->atLeastOnce())
             ->method('get')
             ->will($this->returnValue(serialize($configData)));
        $file->expects($this->never())
             ->method('set')
             ->will($this->returnValue(true));

        $config = $this->getMock('MP\Config\Config', array('getCacheEngines', 'getSourceEngine'));
        $config->expects($this->never())
               ->method('getSourceEngine')
               ->will($this->returnValue($mysql));
        $config->expects($this->any())
               ->method('getCacheEngines')
               ->will($this->returnValue(array($apc, $file)));
        $config->load('foo');
        $actual = $config->toArray();
        $this->assertEquals($configData, $actual);
    }

    /**
     * Prove that when all cache engines miss (get() returns false), we 
     * update all cache engines (call set()) after fetching from source.
     */
    public function testCacheSetWithMultipleEngines()
    {
        $configData = array(
            "db.host" => "10.0.0.1",
            "db.port" => "3306");

        // Canonical data store; must call get() once
        $mysql = $this->getMock('MP\Config\Storage\MysqlStorage', array('get'));
        $mysql->expects($this->once())
              ->method('get')
              ->will($this->returnValue('any-string'));

        // APC cache will miss on get(), then must call set() once.
        $apc = $this->getMock('MP\Config\Storage\ApcStorage', array('get', 'set'));
        $apc->expects($this->atLeastOnce())
            ->method('get')
            ->will($this->returnValue(false));
        $apc->expects($this->once())
            ->method('set')
            ->will($this->returnValue(true));

        // File cache will miss on get(), then must call set() once.
        $file = $this->getMock('MP\Config\Storage\FileStorage', array('get', 'set'));
        $file->expects($this->atLeastOnce())
             ->method('get')
             ->will($this->returnValue(false));
        $file->expects($this->atLeastOnce())
             ->method('set')
             ->will($this->returnValue(true));

        // Setup the config mock
        $config = $this->getMock('MP\Config\Config',
                array('getCacheEngines', 'getSourceEngine', 'parseSpec'));
        $config->expects($this->any())
               ->method('getSourceEngine')
               ->will($this->returnValue($mysql));
        $config->expects($this->any())
               ->method('getCacheEngines')
               ->will($this->returnValue(array($apc, $file)));
        $config->expects($this->any())
               ->method('parseSpec')
               ->will($this->returnValue($configData));

        $config->load('foo');
        $actual = $config->toArray();
        $this->assertEquals($configData, $actual);
    }

    /**
     * Prove that all cache engines are updated on setCache()
     */
    public function testSetCache()
    {
        $s1 = $this->getMock('MP\Config\Storage\FileStorage', array('set'));
        $s1->expects($this->once())
           ->method('set')
           ->will($this->returnValue(true));
        $s2 = $this->getMock('MP\Config\Storage\FileStorage', array('set'));
        $s2->expects($this->once())
           ->method('set')
           ->will($this->returnValue(true));

        $config = $this->getMock('MP\Config\Config', array('getCacheEngines'));
        $config->expects($this->any())
               ->method('getCacheEngines')
               ->will($this->returnValue(array($s1, $s2)));
        $actual = $config->setCache('some-key', 'non-false-content');
        $this->assertTrue($actual);
    }

    /**
     * Prove that setCache() calls set() on all cache engines even if one fails.
     */
    public function testSetCacheWithFailures()
    {
        $s1 = $this->getMock('MP\Config\Storage\FileStorage', array('set'));
        $s1->expects($this->once())
           ->method('set')
           ->will($this->returnValue(false));
        $s2 = $this->getMock('MP\Config\Storage\FileStorage', array('set'));
        $s2->expects($this->once())
           ->method('set')
           ->will($this->returnValue(true));

        $config = $this->getMock('MP\Config\Config', array('getCacheEngines'));
        $config->expects($this->any())
               ->method('getCacheEngines')
               ->will($this->returnValue(array($s1, $s2)));
        $actual = $config->setCache('some-key', 'non-false-content');
        $this->assertFalse($actual);
    }
}
