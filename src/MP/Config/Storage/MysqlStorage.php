<?php
namespace MP\Config\Storage;

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
     *  - database: Name of database containing the config table.
     *  - table:    Name of database table containing the config data.
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
                    INSERT INTO %s ("environment", "config")
                    VALUES ("%s", "%s")
                    ON DUPLICATE KEY UPDATE config="%s"',
                    mysql_real_escape_string($this->table),
                    mysql_real_escape_string($key),
                    mysql_real_escape_string($value),
                    mysql_real_escape_string($value));
        $res = mysql_query($sql, $conn);
        if (!$res) {
            throw new ConfigException(sprintf(
                        "Unable to set key: %s. (%s)",
                        $key, mysql_error($conn)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $conn = $this->connect();
        $sql  = sprintf('SELECT config FROM %s WHERE environment="%s"',
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

    public function getDdl()
    {
        return "
            CREATE TABLE config_environments (
                environment VARCHAR(255) NOT NULL PRIMARY KEY,
                config TEXT default ''
            );
        ";
    }
}
