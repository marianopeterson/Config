#!/usr/bin/env php
<?php
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require dirname(dirname(__FILE__)) . '/vendor/docopt/docopt/src/docopt.php';

use MP\Config\Config;
use MP\Config\EnvironmentParser;
use MP\Config\Storage\ApcStorage;
use MP\Config\Storage\FileStorage;
use MP\Config\Storage\MysqlStorage;

$usage = <<<EOT
Tool for seeing the configuration that results when combining environments and
storage engines.

Compiles a configuration by layering settings from the named environments.
The following example loads settings from "common", then overrides them
with settings from "dev", and finally overrides that with settings from "john".

Example: cli.php common dev john

Usage:
    cli.php [--db-host=127.0.0.1:3306] [--db-user=root] [--db-pass=prompt] [--db-name=mp_config] [--db-table=configuation] [--skip-cache] [--filepath=path] <environment>...
    cli.php (-h | --help)
    cli.php --version
    cli.php --init-db

Options:
    -h --help         Show this screen.
    --version         Show version.
    --db-host=host    Database host [default: 127.0.0.1:3306].
    --db-user=user    Database user [default: root].
    --db-pass=prompt  Database password. Default is to user interactive prompt [default: prompt].
    --db-name=name    Database name [default: mp_config].
    --db-table=table  Database table [default: configuration].
    --filepath=path   Path to directory for FileStorage [default: /tmp/mp_config/].
    --init-db         Initialize the database.
    --skip-cache      Reload the spec from the source (skip cache lookup) [default: false].
EOT;
$args = Docopt::handle($usage, array('version'=>'1.0'));

if ($args['--db-pass'] == 'prompt') {
    print "Enter database password: ";
    $args['--db-pass'] = Seld\CliPrompt\CliPrompt::hiddenPrompt();
}

if ($args['--init-db']) {
    MysqlStorage::initDb(
        $args['--db-host'],
        $args['--db-user'],
        $args['--db-pass'],
        $args['--db-name'],
        $args['--db-table']);
    exit(0);
}

$config = Config::getInstance()
//  ->setSource(new FileStorage(array('root' => $args['--filepath'])))
    ->setSourceEngine(new MysqlStorage(array(
                    'host'     => $args['--db-host'],
                    'username' => $args['--db-user'],
                    'password' => $args['--db-pass'],
                    'database' => $args['--db-name'],
                    'table'    => $args['--db-table'])))
    // ->addCacheEngine(new ApcStorage())
    ->addCacheEngine(new FileStorage(array('root' => $args['--filepath'])))
    ->load($args['<environment>'], $args['--skip-cache']);

printf("Compiled configuration is: %s\n", print_r($config->toArray(), true));
