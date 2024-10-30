<?php

defined('ABSPATH') or exit();

class ConnectaFailedToCreateOrder extends Exception
{
    public function __construct($message)
    {
        if (empty($message)) {
            $message = "Failed to create order.";
        }
        parent::__construct($message, 0, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": {$this->message}\n";
    }
}
