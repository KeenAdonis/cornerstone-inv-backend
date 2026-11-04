<?php

namespace App\Services;

use Carbon\Carbon;

class SystemAvailabilityService
{
    public function isAvailable(): bool
    {
        if (! config('system-availability.enabled')) {
            return true;
        }

        $lockAt = config('system-availability.lock_at');

        // Empty date means immediate lock.
        if (empty($lockAt)) {
            return false;
        }

        $timezone = config(
            'system-availability.timezone',
            'Asia/Manila'
        );

        $lockDate = Carbon::parse($lockAt, $timezone);

        return now($timezone)->lt($lockDate);
    }
}
