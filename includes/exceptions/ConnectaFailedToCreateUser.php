<?php

defined('ABSPATH') or exit();

class ConnectaFailedToCreateUser extends Exception
{
    public function __construct()
    {
        parent::__construct("Failed to create user.", 0, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": {$this->message}\n";
    }
}
