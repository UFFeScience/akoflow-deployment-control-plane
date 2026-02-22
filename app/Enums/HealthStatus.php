<?php

namespace App\Enums;

enum HealthStatus: string
{
    case HEALTHY = 'HEALTHY';
    case UNHEALTHY = 'UNHEALTHY';
}
