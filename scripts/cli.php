#!/usr/bin/env php
<?php
$root = dirname(__FILE__) . '/MP/Config';
require_once($root . '/Config.php');
require_once($root . '/ConfigEnvironment.php');
require_once($root . '/ConfigException.php');
require_once($root . '/Storage/StorageInterface.php');
require_once($root . '/Storage/ApcStorage.php');
require_once($root . '/Storage/FileStorage.php');
require_once($root . '/Storage/MysqlStorage.php');

$usage = <<<EOT
Usage: {$argv[0]} OPTIONS environment

OPTIONS
    -h, --help      Show this help message.
    -n, --no-inheritance Prevents lookup of inherited enviroments.
    -r, --reload    Reload the spec from the source (skip cache lookup).

EOT;

$environments = null;
$inheritance  = true;
$reload       = false;

for ($i = 1; $i < count($argv); $i++) {
    if (in_array($argv[$i], array("-h", "--help"))) {
       print $usage;
       exit(0);
    }
    if (in_array($argv[$i], array("-n", "--no-inheritance"))) {
        $inheritance = false;
        continue;
    }
    if (in_array($argv[$i], array("-r", "--reload"))) {
        $reload = true;
        continue;
    }
    $environments = $argv[$i];
}

if ($inheritance) {
    $parser = new ConfigEnvironment();
    $environments = $parser->getLineage($environments);
}

/*
$config = Config::getInstance()
    ->setSource(new FileStorage(array('root' => $root . "/")))
    ->setCache(new FileStorage(array('root' => "/tmp/")))
    ->load($environments);
*/

$config = Config::getInstance()
    ->setSourceEngine(new MysqlStorage(array(
                    'host'     => '127.0.0.1',
                    'username' => 'test',
                    'password' => 'test',
                    'database' => 'tmp',
                    'table'    => 'ConfigEnvironments')))
    ->addCacheEngine(new ApcStorage())
    ->addCacheEngine(new ApcStorFileStorage(array('root' => "/tmp/")))
    ->load($environments, $reload);

print "Config: ";
print_r($config->toArray());
print "\n";
