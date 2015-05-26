<?php
namespace MP\Config\Storage;

use MP\Config\ConfigException;

class MysqlStorage
implements StorageInterface
{
    private $host;
    private $username;
    private $password;
    private $database;
    private $table;

    /**
     * Defines the database connection.
     * {@inheritdoc}
     *
     * Constructor options for MysqlStorage:
     *  - host:     Name of host where the database resides.
     *              Port can optionally be appended, e.g., "127.0.0.1:3306"
     *  - database: Name of database containing the configuration table.
     *  - table:    Name of database table containing the configuration data.
     *  - username: Database user name.
     *  - password: Database password.
     */
    public function __construct(array $opts = array())
    {
        $required = array(
                'host'     => true,
                'username' => true,
                'password' => true,
                'database' => true,
                'table'    => true);
        $invalidOpts = array_diff_key($opts, $required);
        if ($invalidOpts) {
            throw new ConfigException(sprintf("Missing required options: %s",
                        implode(", ", array_keys($invalidOpts))));
        }
        $this->host     = $opts['host'];
        $this->username = $opts['username'];
        $this->password = $opts['password'];
        $this->database = $opts['database'];
        $this->table    = $opts['table'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $conn = $this->connect();
        $sql  = sprintf('
                    INSERT INTO %s ("environment", "configuration")
                    VALUES ("%s", "%s")
                    ON DUPLICATE KEY UPDATE configuration="%s"',
                    mysql_real_escape_string($this->table),
                    mysql_real_escape_string($key),
                    mysql_real_escape_string($value),
                    mysql_real_escape_string($value));
        $res = mysql_query($sql, $conn);
        if (!$res) {
            throw new ConfigException(sprintf(
                        "Unable to set key: %s. (%s)",
                        $key,
                        mysql_error($conn)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $conn = $this->connect();
        $sql  = sprintf('SELECT configuration FROM %s WHERE environment="%s"',
                    mysql_real_escape_string($this->table),
                    mysql_real_escape_string($key));
        $res = mysql_query($sql, $conn);
        if ($res === false || mysql_num_rows($res) == 0) {
            return false;
        }
        return mysql_result($res, 0);
    }

    public function connect()
    {
        $conn = mysql_pconnect($this->host, $this->username, $this->password);
        if (!$conn) {
            throw new ConfigException(sprintf(
                    "Unable to connect to database host: %s@%s/%s",
                    $this->username,
                    $this->host,
                    $this->database));
        }
        if (!mysql_select_db($this->database, $conn)) {
            throw new ConfigException(sprintf(
                    "Unable to select database: %s@%s/%s",
                    $this->username,
                    $this->host,
                    $this->database));
        }
        return $conn;
    }

    /**
     * Creates a database and table to store configurations.
     * Does not change databases, tables, or rows if they already exist.
     */
    public static function initDb($host, $user, $pass, $db, $table)
    {
        $conn = mysql_connect($host, $user, $pass);
        $sql  = sprintf(
            'CREATE DATABASE IF NOT EXISTS %s;',
            mysql_real_escape_string($db));
        $res = mysql_query($sql, $conn);
        if ($res === false) {
            throw new \Exception(sprintf("Query failed: %s", $sql));
        }

        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS %s.%s (
                environment VARCHAR(255) NOT NULL PRIMARY KEY,
                configuration TEXT default '');",
            mysql_real_escape_string($db),
            mysql_real_escape_string($table));
        $res = mysql_query($sql, $conn);
        if ($res === false) {
            throw new \Exception(sprintf("Query failed: %s", $sql));
        }

        $sql = sprintf(
            "INSERT INTO %s.%s (environment, configuration)
            VALUES
                ('demo.common', '# Demo configuration\n\ndb.write.host=127.0.0.1\ndb.write.port=3306\n'),
                ('demo.custom', '# Demo configuration\n\ndb.write.host=my.com\n')
            ON DUPLICATE KEY UPDATE environment=environment;",
            mysql_real_escape_string($db),
            mysql_real_escape_string($table));
        $res = mysql_query($sql, $conn);
        if ($res === false) {
            throw new \Exception(sprintf("Query failed: %s", $sql));
        }
        $row = mysql_affected_rows($conn);
        if ($row == 0) {
            print "Database already exists. No action taken.\n";
        } else {
            print "Database created and initialized.\n";
        }
        $pass_arg = $pass == '' ? '' : '-p';

        if (strpos($host, ':') !== FALSE) {
            list($host, $port_arg) = explode(':', $host);
            $port_arg = "--port=$port_arg";
        } else {
            $port = '';
        }
        print "View it with:\n"
            . "mysql -h$host $port_arg -u$user $pass_arg $db -e 'select * from $table;'\n";
    }

}
