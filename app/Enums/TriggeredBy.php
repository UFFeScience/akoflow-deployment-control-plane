<?php

namespace App\Enums;

enum TriggeredBy: string
{
    case USER = 'USER';
    case SYSTEM = 'SYSTEM';
    case AKOFLOW = 'AKOFLOW';
}
