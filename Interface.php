<?php

interface Config_Interface
{
    /**
     * Gets a common instance of the Config object.
     * Implements the singleton pattern.
     *
     * @return Config_Interface
     */
    public function getInstance();

    /**
     * Gets the value for a config $key.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Loads configuration specs from a set of storage nodes. The first spec
     * serves as the base and subsequent specs override the previous ones.
     *
     * If called again, the new storage spec overrides the keys that have
     * already been set. Call reset() to clear the previously set keys.
     *
     * @param Config_Storage_Interface|array<Config_Storage_Interface>
     *
     * @return array Map of configuration keys and values.
     */
    public function load($storage);

    /**
     * Parses a config spec and returns the result as an array.
     *
     * @param string $spec Config spec
     *
     * @return array Map of configuration keys and values.
     */
    public function parseSpec($spec);
}
