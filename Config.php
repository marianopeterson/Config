<?php

class Config
{
    const LINE_DELIMITER = "\n";
    const KEY_DELIMITER  = "=";
    const TYPE_DELIMITER = ",";
    const LIST_DELIMITER = ",";

    private $keys = array();
    private static $instance = null;

    /**
     * You probably want the Config::getInstance() method instead. It works
     * similarly to the Singleton pattern (always returns the same instance)
     * and improves performance and reduces memory usage.
     *
     * The constructor is only scoped public to facilitate unit testing
     * (specifically to allow mocked methods to isolate units of code).
     */
    public function __construct()
    {
    }

    /**
     * Fetch a common instance of Config. This allows usage similar to the
     * Singleton pattern, but note that the constructor is still public
     * in order to facilitate testing (allowing us to mock the object).
     *
     * @return Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key)
    {
        if (!isset($this->keys[$key])) {
            throw new Config_Exception("Undefined config key: $key");
        }
        return $this->keys[$key];
    }

    public function toArray()
    {
        return $this->keys;
    }

    public function load($storage)
    {
        if (!is_array($storage)) {
            $storage = array($storage);
        }

        foreach ($storage as $s) {
            $spec       = $s->fetch();
            $config     = $this->parseSpec($spec);
            $this->keys = array_merge($this->keys, $config);
        }
        return $this->keys;
    }

    public function parseSpec($spec)
    {
        $lines  = explode(self::LINE_DELIMITER, $spec);
        $config = array();
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comment lines
            if (empty($line) ||
                    substr($line, 0, 1) == '#' ||
                    substr($line, 0, 2) == '//') {
                continue;
            }
            if (strpos($line, self::KEY_DELIMITER) === false) {
                throw new Config_Exception(sprintf(
                            "Invalid configuration syntax (missing delimiter %s): %s",
                            self::KEY_DELIMITER,
                            $line));
            }

            // Parse key, value, and type
            list ($key, $value) = explode(self::KEY_DELIMITER, $line, 2);
            if (strpos($key, self::TYPE_DELIMITER) !== false) {
                list ($key, $type) = explode(self::TYPE_DELIMITER, $key, 2);
            } else {
                $type = 'string';
            }
            $key   = trim($key);
            $type  = trim($type);
            $value = $this->cast($value, $type);

            $config[$key] = $value;
        }
        return $config;
    }

    /**
     * Converts a string into array elements, delimiting elements using $delim.
     * Note that $delim can be escaped by a backslash.
     *
     * @param string $input Delimited list of elements to separate.
     * @param string $delim Single character delimiter.
     * @return array<string>
     */
    public function splitString($input, $delim=',')
    {
        $values = array();
        $delim  = preg_quote($delim);
        // only split on delimiters that are not preceeded by escape characters.
        $parts = preg_split("/(?<!\\\){$delim}/", $input);
        foreach ($parts as &$match) {
            // unescape delimiters that are preceeded by a backslash
            // e.g., foo\,bar => foo,bar
            $values[] = preg_replace("/\\\\{$delim}/", $delim, trim($match));
        }
        return $values;
    }

    /**
     * Casts a $value to a specified $type. If $type ends with [], the
     * value is split into an array whose elements are cast to $type.
     *
     * @param $value The source value.
     * @param $type  The type the source value will be cast to. If $value ends
     *               in [], the $value is split into an array using $delim and
     *               each of the elements are cast to $type.
     * @param $delim Used only if $type ends with [], $delim is the character
     *               used to split $value into array elements.
     * @return mixed
     */
    public function cast($value, $type)
    {
        $value = trim($value);
        if (substr($type, -2) == '[]') {
            // Handle list types (int[], float[], etc.)
            $type  = substr($type, 0, -2);
            $value = $this->splitString($value);
            foreach ($value as &$v) {
                $v = $this->cast($v, $type);
            }
            return $value;
        }

        switch ($type) {
            case 'bool':
            case 'boolean':
                $lower  = strtolower($value);
                $falsy  = array('false', 'no', 'off', 'disabled');
                $truthy = array('true', 'yes', 'on', 'enabled');
                if (in_array($lower, $falsy)) {
                    $value = false;
                } elseif (in_array($lower, $truthy)) {
                    $value = true;
                } else {
                    $value = (bool) $value;
                }
                break;

            case 'int':
            case 'integer':
                $value = (int) $value;
                break;

            case 'float':
            case 'double':
                $value = (float) $value;
                break;

            case 'string':
            default:
                $value = (string) $value;
                break;
        }
        return $value;
    }
}
