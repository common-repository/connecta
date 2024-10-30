<?php

defined('ABSPATH') or exit();

class ConnectaUserEmailMissing extends Exception
{
    public function __construct()
    {
        parent::__construct("Email is missing", 0, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": {$this->message}\n";
    }
}
