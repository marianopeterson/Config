<?php

class Config
{
    const LINE_DELIMITER = "\n";
    const KEY_DELIMITER  = "=";
    const TYPE_DELIMITER = ",";
    const LIST_DELIMITER = ",";

    /**
     * @var Config Instance of self (used for singleton type access).
     */
    private static $instance = null;

    /**
     * @var array<string:mixed> Map of configuration options.
     */
    private $keys = array();

    /**
     * @var Config_Storage_Interface Storage engine from which environment
     *                               specs will be fetched.
     */
    private $source;

    /**
     * @var array<Config_Storage_Interface> List of storage engines from which
     *                                      to try and fetch parsed config
     *                                      specs for environments. The storage
     *                                      engines are queried sequentially
     *                                      using the Chain of Responsibility
     *                                      pattern.
     */
    private $cache = array();

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

    /**
     * Fetch a key from the Config object.
     *
     * @throws Config_Exception If the key is not defined.
     * @return mixed The requested key.
     */
    public function get($key)
    {
        if (!isset($this->keys[$key])) {
            throw new Config_Exception("Undefined config key: $key");
        }
        return $this->keys[$key];
    }

    /**
     * Get the Config object's key-value pairs as an associative array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->keys;
    }

    /**
     * Fetches, parses, and loads config specs for the given environments into
     * this config object.
     *
     * @param string|array<string> $environments List of environment names
     *                                           whose config specs will be
     *                                           fetched, parsed, and loaded
     *                                           into this config object.
     * @param bool                 $reload       Set True to skip cache and
     *                                           reload spec from source.
     *
     * @return Config (supports fluent interface)
     */
    public function load($environments, $reload=false)
    {
        if (!is_array($environments)) {
            $environments = array($environments);
        }

        // HOOK: cache get
        $cacheKey = md5(serialize($environments));
        if (!$reload) {
            $content  = $this->getCache($cacheKey);
            if ($content !== false) {
                $this->keys = unserialize($content);
                return $this;
            }
        }

        if (!$this->getSourceEngine()) {
            throw new Config_Exception("Must set config source before loading. See ->source().");
        }
        foreach ($environments as $environment) {
            // HOOK: source get
            $spec       = $this->getSourceEngine()->get($environment);
            $config     = $this->parseSpec($spec);
            $this->keys = array_merge($this->keys, $config);
        }

        // HOOK: cache set
        $this->setCache($cacheKey, serialize($this->keys));

        return $this;
    }

    /**
     * Parses a config spec into a set of key-value pairs.
     *
     * @param string $spec The raw config spec.
     * @return array Map of key-value pairs.
     */
    public function parseSpec($spec)
    {
        $lines  = explode(self::LINE_DELIMITER, $spec);
        $config = array();
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comment lines
            if (empty($line) ||
                    substr($line, 0, 1) == ';' ||
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
        }
        return $value;
    }

    /**
     * Set the storage engine that will be used to access unparsed config specs.
     *
     * @param Config_Storage_Interface $source Storage engine used to access
     *                                         unparsed config specs.
     * @return Config (supports fluent interface)
     */
    public function setSourceEngine(Config_Storage_Interface $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get the storage engine that will be used to access unparsed config specs.
     *
     * @return Config_Storage_Interface
     */
    public function getSourceEngine()
    {
        return $this->source;
    }

    /**
     * Add a storage engine to the list of cache engines that will be queried.
     * Caches are queried in the order they are added.
     *
     * @param Config_Storage_Interface $cache Storage engine to use for cache.
     * @return Config (Supports fluent interface)
     */
    public function addCacheEngine(Config_Storage_Interface $cache)
    {
        $this->cache[] = $cache;
        return $this;
    }

    /**
     * Get the list of storage engines used for cache. The engines must be
     * accessed sequentially.
     *
     * @return array<Config_Storage_Interface>
     */
    public function getCacheEngines()
    {
        return $this->cache;
    }

    /**
     * Attempts to fetch a key from a list of cache engines.
     * Engines that get a cache miss will automatically be
     * repopulated by the first engine that gets a cache hit.
     *
     * @param string $key The cache key to lookup.
     * @return mixed False on cache miss, otherwise the contents.
     */
    public function getCache($key)
    {
        $engines = $this->getCacheEngines();
        if (empty($engines)) {
            return false;
        }

        // Check caches for key
        $hit = 0; // index of cache engine that hit
        for ($i = 0; $i < count($engines); $i++) {
            $content = $engines[$i]->get($key);
            if ($content !== false) {
                $hit = $i;
                break;
            }
        }

        // Set the cache key in the engines that missed
        $this->setCache($key, $content, $hit);
        return $content;
    }

    /**
     * Writes the content to the key in each cache engine.
     * Writing stops on the first engine that misses.
     *
     * @param string $key     Key to write to.
     * @param mixed  $content Content to write.
     * @param int    $limit   Max number of engines to update.
     *
     * @return bool True if all engines were updated.
     */
    public function setCache($key, $content, $limit=null)
    {
        $engines = $this->getCacheEngines();
        if (empty($engines)) {
            return false;
        }
        $engineCount = count($engines);
        $limit       = ($limit === null) ? $engineCount : min($engineCount, $limit);
        $success     = true;
        for ($i = 0; $i < $limit; $i++) {
            if (!$engines[$i]->set($key, $content)) {
                $success = false;
            }
        }
        return $success;
    }
}
