<?php
$root = dirname(__FILE__);
require_once($root . '/Config.php');
require_once($root . '/Config/Exception.php');
require_once($root . '/Config/Storage/Interface.php');
require_once($root . '/Config/Storage/File.php');


$ini = array();
for ($i = 1; $i < count($argv); $i++) {
    $ini[] = new Config_Storage_File($root . "/" . $argv[$i]);
}

$config = Config::getInstance();
$config->load($ini);

print_r($config->toArray());

print_r($config->get("db.hosts"));
