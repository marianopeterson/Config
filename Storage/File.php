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
        return file_get_contents($this->filePath);
    }
}
