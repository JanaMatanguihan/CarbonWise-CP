<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(
        string $gSuite,
        string $module,
        string $activity,
        string $description,
        string $status = 'Success',
        ?int $recordId = null,
        array $metadata = []
    ): void {

        ActivityLog::create([
            'g_suite'     => $gSuite,
            'module'      => $module,
            'activity'    => $activity,
            'description' => $description,
            'status'      => $status,
            'record_id'   => $recordId,
            'metadata'    => $metadata,
        ]);
    }
}