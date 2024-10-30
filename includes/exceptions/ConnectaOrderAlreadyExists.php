<?php

defined('ABSPATH') or exit();

class ConnectaOrderAlreadyExists extends Exception
{
    public function __construct()
    {
        parent::__construct("An order with that eBay Order ID already exists", 0, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": {$this->message}\n";
    }
}
