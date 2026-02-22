<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedOrganizationAccessException extends Exception
{
    public function __construct($message = "Unauthorized access to organization", $code = 403)
    {
        parent::__construct($message, $code);
    }
}
