<?php

namespace App\Enums;

enum ProviderType: string
{
    case CLOUD = 'CLOUD';
    case ON_PREM = 'ON_PREM';
    case HPC = 'HPC';
    case LOCAL = 'LOCAL';
}
