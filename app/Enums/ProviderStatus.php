<?php

namespace App\Enums;

enum ProviderStatus: string
{
    case ACTIVE = 'ACTIVE';
    case DEGRADED = 'DEGRADED';
    case DOWN = 'DOWN';
    case MAINTENANCE = 'MAINTENANCE';
}
