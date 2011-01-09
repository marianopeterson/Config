<?php
interface Config_Storage_Interface
{
    /**
     * Fetches an unparsed config spec from a storage node.
     */
    public function fetch();
}
