<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('audit:prune {--days= : Keep entries newer than this many days (default: config audit.retention_days)}')]
#[Description('Delete audit log entries older than the retention period')]
class PruneAuditLogs extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $days = (int) ($this->option('days') ?? config('audit.retention_days'));

        if ($days < 1) {
            $this->error('Retention must be at least 1 day.');

            return self::INVALID;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        AuditLog::where('created_at', '<', $cutoff)->chunkById(1000, function ($logs) use (&$deleted) {
            $deleted += AuditLog::whereKey($logs->modelKeys())->delete();
        });

        if ($deleted > 0) {
            $audit->record('audit_pruned', null, null, ['deleted' => $deleted, 'older_than' => $cutoff->toDateTimeString()], userId: null);
        }

        $this->info("Deleted {$deleted} audit log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
