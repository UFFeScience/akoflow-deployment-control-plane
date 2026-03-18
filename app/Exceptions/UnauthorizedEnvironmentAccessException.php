<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedEnvironmentAccessException extends Exception
{
    public function __construct($message = 'Unauthorized access to environment', $code = 403)
    {
        parent::__construct($message, $code);
    }
}
