<?php

defined('ABSPATH') or exit();

class ConnectaNotAnEbayOrder extends Exception
{
    public function __construct()
    {
        parent::__construct("The provided order id is not an eBay Order!", 0, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": {$this->message}\n";
    }
}
