<?php

namespace App\Exceptions;

use Exception;

class InvalidPasswordException extends Exception
{
    public function __construct($message = "Invalid password", $code = 401)
    {
        parent::__construct($message, $code);
    }
}
