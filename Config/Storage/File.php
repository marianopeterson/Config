<?php
class Config_Storage_File
implements Config_Storage_Interface
{
    private $root;

    public function __construct(array $opts = array())
    {
        $default = array('root' => '');
        $invalid = array_diff_key($opts, $default);
        if ($invalid) {
            throw new Config_Exception(sprintf("Invalid options: %s",
                        implode(", ", $invalid)));
        }
        $opts = array_merge($default, $opts);
        $this->root = $opts['root'];
    }

    public function set($key, $value)
    {
        try {
            $r = file_put_contents($this->root . $key, $value);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function get($key)
    {
        $filePath = $this->root . $key;
        if (!is_readable($filePath)) {
            return false;
        }
        return file_get_contents($filePath);
    }
}
