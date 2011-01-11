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
     * Loads configuration specs for a set of environments. The first spec
     * serves as the base and subsequent specs override the previous ones.
     *
     * If load() is called again, the new environment overrides the keys that
     * were set earlier.
     *
     * @param Config_Storage_Interface $environment Environment whose config
     *                                              spec will be loaded.
     *
     * @return Config_Interface Reference to itself (supports fluent interface).
     */
    public function load($environments);

    /**
     * Parses a config spec and returns the result as an array.
     *
     * @param string $spec Config spec
     *
     * @return array Map of configuration keys and values.
     */
    public function parseSpec($spec);
}
