<?php
interface Config_Storage_Interface
{
    /**
     * Constructor options vary between implementations.
     *
     * @param array $opts Parameters described as a set of key,value pairs.
     */
    public function __construct(array $opts = array());

    /**
     * Stores a value to the specified key.
     *
     * @param string $key   Identifier for the value to be stored.
     * @param mixed  $value Value to be stored.
     *
     * @return bool True on success, False on failure.
     */
    public function set($key, $value);

    /**
     * Fetches an unparsed config spec from a storage node.
     *
     * @param string $key Identifier for the data to be fetched.
     * @return mixed Data at $key, or boolean FALSE if key is not accessible.
     */
    public function get($key);
}
