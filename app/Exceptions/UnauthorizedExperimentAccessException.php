<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedExperimentAccessException extends Exception
{
    public function __construct($message = 'Unauthorized access to experiment', $code = 403)
    {
        parent::__construct($message, $code);
    }
}
