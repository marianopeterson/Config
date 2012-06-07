<?php
namespace MP\Config\Storage;

use MP\Config\ConfigException;

class FileStorage
implements StorageInterface
{
    private $root;

    public function __construct(array $opts = array())
    {
        $default = array('root' => '');
        $invalid = array_diff_key($opts, $default);
        if ($invalid) {
            throw new ConfigException(sprintf("Invalid options: %s",
                        implode(", ", $invalid)));
        }
        $opts = array_merge($default, $opts);
        $this->root = $opts['root'];
    }

    public function set($key, $value)
    {
        $r = @file_put_contents($this->root . $key, $value);
        if ($r === false) {
            return false;
        }
        return true;
    }

    public function get($key)
    {
        $path = $this->root . $key;
        $contents = @file_get_contents($path);
        if ($contents === false) {
            return false;
        }
        return $contents;
    }
}
