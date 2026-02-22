<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedProjectAccessException extends Exception
{
    public function __construct($message = "Unauthorized access to project", $code = 403)
    {
        parent::__construct($message, $code);
    }
}
