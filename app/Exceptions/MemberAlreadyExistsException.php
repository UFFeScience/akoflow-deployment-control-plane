<?php

namespace App\Exceptions;

use Exception;

class MemberAlreadyExistsException extends Exception
{
    public function __construct($message = "Member already exists in organization", $code = 409)
    {
        parent::__construct($message, $code);
    }
}
