#!/usr/bin/env php
<?php
$root = dirname(__FILE__);
require_once($root . '/Config.php');
require_once($root . '/Config/Environment.php');
require_once($root . '/Config/Exception.php');
require_once($root . '/Config/Storage/Interface.php');
require_once($root . '/Config/Storage/Apc.php');
require_once($root . '/Config/Storage/File.php');
require_once($root . '/Config/Storage/MySQL.php');

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
    $parser = new Config_Environment();
    $environments = $parser->getLineage($environments);
}

/*
$config = Config::getInstance()
    ->setSource(new Config_Storage_File(array('root' => $root . "/")))
    ->setCache(new Config_Storage_File(array('root' => "/tmp/")))
    ->load($environments);
*/

$config = Config::getInstance()
    ->setSourceEngine(new Config_Storage_MySQL(array(
                    'host'     => '127.0.0.1',
                    'username' => 'test',
                    'password' => 'test',
                    'database' => 'tmp',
                    'table'    => 'config_environments')))
    ->addCacheEngine(new Config_Storage_Apc())
    ->addCacheEngine(new Config_Storage_File(array('root' => "/tmp/")))
    ->load($environments, $reload);

print "Config: ";
print_r($config->toArray());
print "\n";
