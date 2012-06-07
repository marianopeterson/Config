<?php
namespace MP\Config\Storage;

class MemcachedStorage
implements StorageInterface
{
    /**
     * @var array List of memcache servers to connect to. Sample:
     *              array(
     *                  array(host, port[, weight]),
     *                  array(host, port[, weight])
     *              )
     */
    private $servers;

    public function __construct(array $opts = array())
    {
        $required    = array('servers' => true);
        $invalidOpts = array_diff_key($opts, $required);
        $missingOpts = array_diff_key($required, $opts);
        if ($invalidOpts) {
            throw new ConfigException(sprintf("Invalid parameters: %s",
                        implode(", ", $invalidOpts)));
        }
        if ($missingOpts) {
            throw new ConfigException(sprintf("Missing required parameters: %s",
                        implode(", ", $missingOpts)));
        }
        foreach ($opts['servers'] as $server) {
            if (count($server) != 2 && count($server) != 3) {
                throw new ConfigException("Invalid parameters.");
            }
        }
        $this->servers = $opts['servers'];
    }

    public function set($key, $value)
    {
        $m = new Memcached();
        $m->addServers($this->servers);
        $r = $m->set($key, $value);
        return $r;
    }

    public function get($key)
    {
        $m = new Memcached();
        $m->addServers($this->servers);
        $data = $m->get($key);
        if ($data === false) {
            return false;
        }
        return $data;
    }
}
