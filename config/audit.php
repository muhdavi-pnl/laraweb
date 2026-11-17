<?php

return [
    // Audit entries older than this many days are removed by `audit:prune` (scheduled daily).
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
