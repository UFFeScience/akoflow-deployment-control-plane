<?php

namespace App\Enums;

enum DeploymentStatus: string
{
    case PROVISIONING = 'PROVISIONING';
    case RUNNING = 'RUNNING';
    case STOPPED = 'STOPPED';
    case ERROR = 'ERROR';
}
