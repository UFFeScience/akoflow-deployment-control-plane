<?php

namespace App\Enums;

enum RuntimeType: string
{
    case AKOFLOW = 'AKOFLOW';
    case FLARE = 'FLARE';
    case HPC = 'HPC';
    case CUSTOM = 'CUSTOM';
}
