<?php

require_once(dirname(__FILE__). '/Config2.php');

$config = Config::getInstance();

$ini = dirname(__FILE__) . '/test.ini';
$config->load(new Config_Storage_File($ini));


print_r($config->toArray());
