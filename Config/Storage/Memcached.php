<?php
class Config_Storage_Memcached
implements Config_Storage_Interface
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
            throw new Config_Exception(sprintf("Invalid parameters: %s",
                        implode(", ", $invalidOpts)));
        }
        if ($missingOpts) {
            throw new Config_Exception(sprintf("Missing required parameters: %s",
                        implode(", ", $missingOpts)));
        }
        foreach ($opts['servers'] as $server) {
            if (count($server) != 2 && count($server) != 3) {
                throw new Config_Exception("Invalid parameters.");
            }
        }
        $this->servers = $opts['servers'];
    }

    public function set($key, $value)
    {
        $m = new Memcached();
        $m->addServers($this->servers);
        $r = $m->set($key, $value);
        if ($r === false) {
            throw new Config_Exception(sprintf(
                        "Unable to set key: %s. (%s)",
                        $key, $m->getResultMessage()));
        }
    }

    public function get($key)
    {
        $m = new Memcached();
        $m->addServers($this->servers);
        $data = $m->get($key);
        if ($data === false) {
            throw new Config_Exception(""); //todo: error message
        }
        return $data;
    }
}
