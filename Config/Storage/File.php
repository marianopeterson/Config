<?php
class Config_Storage_File
implements Config_Storage_Interface
{
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function fetch()
    {
        if (!file_exists($this->filePath)) {
            throw new Config_Exception("File does not exist: " . $this->filePath);
        }
        if (!is_readable($this->filePath)) {
            throw new Config_Exception("File is not readable: " . $this->filePath);
        }
        return file_get_contents($this->filePath);
    }
}
