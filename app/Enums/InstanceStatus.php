<?php

namespace App\Enums;

enum InstanceStatus: string
{
    case PROVISIONING = 'PROVISIONING';
    case RUNNING = 'RUNNING';
    case STOPPED = 'STOPPED';
    case TERMINATED = 'TERMINATED';
    case ERROR = 'ERROR';
}
