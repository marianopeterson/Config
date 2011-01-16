<?php
class Config_Storage_File
implements Config_Storage_Interface
{
    public function __construct(array $opts = array())
    {
    }

    public function set($key, $value)
    {
        $result = apc_store($key, array(
                    'data'    => $value,
                    'written' => time()));
        if ($result === false) {
            throw new Config_Exception("Unable to set key: $key");
        }
    }

    public function get($key)
    {
        $store = apc_fetch($key);
        if ($store === false) {
            return false;
        }
        return $store['data'];
    }
}
