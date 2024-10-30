<?php

defined('ABSPATH') or exit();

class ConnectaMissingSku extends Exception
{
    public function __construct()
    {
        parent::__construct("The provided SKU is either missing, or not a valid SKU.", 0, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": {$this->message}\n";
    }
}
