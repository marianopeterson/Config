#!/usr/bin/env php
<?php
use MP\Config\Config;
use MP\Config\ConfigEnvironment;
use MP\Config\Storage\ApcStorage;
use MP\Config\Storage\FileStorage;
use MP\Config\Storage\MysqlStorage;

$root = dirname(dirname(__FILE__)) . '/src/MP/Config';
require_once($root . '/Config.php');
require_once($root . '/ConfigEnvironment.php');
require_once($root . '/ConfigException.php');
require_once($root . '/Storage/StorageInterface.php');
require_once($root . '/Storage/ApcStorage.php');
require_once($root . '/Storage/FileStorage.php');
require_once($root . '/Storage/MysqlStorage.php');

$usage = <<<EOT
Usage: {$argv[0]} OPTIONS environment

e.g.,
    {$argv[0]} --db-host=127.0.0.1:3306 \
               --db-name="config_test" \
               --db-user="app_user" \
               "default,dev,mariano"

OPTIONS
    -h, --help      Show this help message.
    -r, --reload    Reload the spec from the source (skip cache lookup).
    --db-host    Database host
    --db-name    Name of MySQL database
    --db-user    Database user name
    --db-pass    Database user password

EOT;

// From SitePoint: http://www.sitepoint.com/interactive-cli-password-prompt-in-php/
function silentInput($prompt = "Enter Password:")
{
    if (preg_match('/^win/i', PHP_OS)) {
        $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
        file_put_contents(
        $vbscript, 'wscript.echo(InputBox("'
        . addslashes($prompt)
        . '", "", "password here"))');
        $command = "cscript //nologo " . escapeshellarg($vbscript);
        $password = rtrim(shell_exec($command));
        unlink($vbscript);
        return $password;
    } else {
        $command = "/usr/bin/env bash -c 'echo OK'";
        if (rtrim(shell_exec($command)) !== 'OK') {
            trigger_error("Can't invoke bash");
            return;
        }
        $command = "/usr/bin/env bash -c 'read -s -p \""
        . addslashes($prompt)
        . "\" mypassword && echo \$mypassword'";
        $password = rtrim(shell_exec($command));
        echo "\n";
        return $password;
    }
}

$environments = null;
$reload       = false;
$dbHost = '127.0.0.1:3306';
$dbName = null;
$dbUser = null;
$dbPass = null;

for ($i = 1; $i < count($argv); $i++) {
    if (in_array($argv[$i], array("-h", "--help"))) {
       print $usage;
       exit(0);
    }
    if (in_array($argv[$i], array("-r", "--reload"))) {
        $reload = true;
        continue;
    }
    if (substr($argv[$i], 0, 10) == '--db-host=') {
        $dbHost = substr($argv[$i], 10);
        continue;
    }
    if (substr($argv[$i], 0, 10) == '--db-name=') {
        $dbName = substr($argv[$i], 10);
        continue;
    }
    if (substr($argv[$i], 0, 10) == '--db-user=') {
        $dbUser = substr($argv[$i], 10);
        continue;
    }
    if (substr($argv[$i], 0, 10) == '--db-pass=') {
        $dbPass = substr($argv[$i], 10);
        continue;
    }
    $environments = $argv[$i];
}

if ($dbPass === null) {
    $inputStream = fopen("php://stdin", "r");
    do {
        $dbPass = silentInput("Enter database password:");
        // TODO: test db connection, only break if creds are valid.
        break;
    } while(true);
}

// $parser = new ConfigEnvironment();
// $environments = $parser->getLineage($environments);

$environments = array_map('trim', explode(",", $environments));
foreach ($environments as $e) {
    print "Loading environment: $e\n";
}

/*
$config = Config::getInstance()
    ->setSource(new FileStorage(array('root' => $root . "/")))
    ->setCache(new FileStorage(array('root' => "/tmp/")))
    ->load($environments);
*/

$config = Config::getInstance()
    ->setSourceEngine(new MysqlStorage(array(
                    'host'     => $dbHost,
                    'username' => $dbUser,
                    'password' => $dbPass,
                    'database' => $dbName,
                    'table'    => 'ConfigEnvironments')))
    ->addCacheEngine(new ApcStorage())
    ->addCacheEngine(new FileStorage(array('root' => "/tmp/")))
    ->load($environments, $reload);

printf("Config: %s\n", print_r($config->toArray(), true));
