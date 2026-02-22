<?php

namespace App\Exceptions;

use Exception;

class OrganizationNotFoundException extends Exception
{
    public function __construct($message = "Organization not found", $code = 404)
    {
        parent::__construct($message, $code);
    }
}
