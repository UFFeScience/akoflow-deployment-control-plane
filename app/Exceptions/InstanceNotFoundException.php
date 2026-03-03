<?php

namespace App\Exceptions;

use Exception;

class InstanceNotFoundException extends Exception
{
    public function __construct($message = 'Instance not found', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
