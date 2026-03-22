<?php

namespace App\Exceptions;

use Exception;

class ClusterNotFoundException extends Exception
{
    public function __construct($message = 'Deployment not found', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
