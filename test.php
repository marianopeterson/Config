<?php
$root = dirname(__FILE__);
require_once($root . '/Config.php');
require_once($root . '/Config/Exception.php');
require_once($root . '/Config/Storage/Interface.php');
require_once($root . '/Config/Storage/File.php');
require_once($root . '/Config/Storage/MySQL.php');


$environments = array();
for ($i = 1; $i < count($argv); $i++) {
    $environments[] = $argv[$i];
}

/*
$config = Config::getInstance()
    ->setSource(new Config_Storage_File(array('root' => $root . "/")))
    ->setCache(new Config_Storage_File(array('root' => "/tmp/")))
    ->load($environments);
*/

$config = Config::getInstance()
    ->setSource(new Config_Storage_MySQL(array(
                    'host'     => '127.0.0.1',
                    'username' => 'test',
                    'password' => 'test',
                    'database' => 'test',
                    'table'    => 'config_environments')))
    ->setCache(new Config_Storage_File(array('root' => "/tmp/")))
    ->load($environments);

print "Config: ";
print_r($config->toArray());
print "\n";
