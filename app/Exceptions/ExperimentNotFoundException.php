<?php

namespace App\Exceptions;

use Exception;

class ExperimentNotFoundException extends Exception
{
    public function __construct($message = 'Experiment not found', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
