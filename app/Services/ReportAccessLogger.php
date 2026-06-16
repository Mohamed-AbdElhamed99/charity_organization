<?php

namespace App\Services;

use App\Models\ReportAccessLog;
use App\Models\User;

class ReportAccessLogger
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function log(
        User $user,
        string $reportKey,
        string $scopeType,
        int $scopeId,
        string $action,
        int $rowCount,
        array $filters = [],
    ): void {
        ReportAccessLog::create([
            'user_id' => $user->id,
            'report_key' => $reportKey,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'action' => $action,
            'row_count' => max(0, $rowCount),
            'filters' => $filters,
        ]);
    }
}
