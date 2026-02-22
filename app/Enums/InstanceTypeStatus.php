<?php

namespace App\Enums;

enum InstanceTypeStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case UNAVAILABLE = 'UNAVAILABLE';
    case DEPRECATED = 'DEPRECATED';
}
