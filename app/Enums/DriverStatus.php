<?php

declare(strict_types=1);

namespace App\Enums;

enum DriverStatus: string
{
    case Offline = 'offline';
    case Available = 'available';
    case Busy = 'busy';
}
