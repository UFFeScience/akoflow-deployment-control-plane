<?php

namespace App\Enums;

enum ClusterAction: string
{
    case SCALE_UP = 'SCALE_UP';
    case SCALE_DOWN = 'SCALE_DOWN';
}
