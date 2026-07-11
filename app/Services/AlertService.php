<?php

namespace App\Services;

use App\Models\Alert;

class AlertService
{
    /**
     * Create a new alert.
     */
    public static function create(
        string $title,
        string $message,
        string $severity = 'info',
        ?int $userId = null
    ): Alert {

        return Alert::create([
            'user_id'   => $userId,
            'title'     => $title,
            'message'   => $message,
            'severity'  => $severity,
            'is_read'   => false,
        ]);
    }
}