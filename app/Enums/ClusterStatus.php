<?php

namespace App\Enums;

enum ClusterStatus: string
{
    case PROVISIONING = 'PROVISIONING';
    case RUNNING = 'RUNNING';
    case STOPPED = 'STOPPED';
    case ERROR = 'ERROR';
}
