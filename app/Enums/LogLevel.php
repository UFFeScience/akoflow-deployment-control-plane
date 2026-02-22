<?php

namespace App\Enums;

enum LogLevel: string
{
    case INFO = 'INFO';
    case WARN = 'WARN';
    case ERROR = 'ERROR';
}
