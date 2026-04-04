<?php

namespace App\Services;

use App\Repositories\BugSlaRecordRepository;

class BugSlaService
{
    private const SLA_HOURS = [
        'p0' => 4,
        'p1' => 24,
        'p2' => 72,
        'p3' => null, // Next sprint
    ];

    public function __construct(
        private readonly BugSlaRecordRepository $bugSlaRepo,
    ) {}

    public function create(array $data): int
    {
        // Auto-calculate SLA deadline based on severity
        $slaHours = self::SLA_HOURS[$data['severity']] ?? null;
        if ($slaHours) {
            $data['sla_deadline'] = now()->addHours($slaHours)->toDateTimeString();
        }

        return $this->bugSlaRepo->create($data);
    }

    /**
     * Check all active SLAs for breaches.
     */
    public function checkBreaches(): array
    {
        $breached = [];

        $records = \Illuminate\Support\Facades\DB::table('bug_sla_records')
            ->whereNull('breached_at')
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->whereNull('deleted_at')
            ->get();

        foreach ($records as $record) {
            $this->bugSlaRepo->update($record->id, [
                'breached_at' => now(),
            ]);
            $breached[] = $record;
        }

        return $breached;
    }

    /**
     * Increment reopen count when a bug is reopened.
     */
    public function reopen(int $id): void
    {
        $record = $this->bugSlaRepo->findById($id);
        if (!$record) {
            return;
        }

        $this->bugSlaRepo->update($id, [
            'reopen_count' => ($record->reopen_count ?? 0) + 1,
            'breached_at'  => null, // Reset breach
        ]);

        // Recalculate SLA deadline from now
        $slaHours = self::SLA_HOURS[$record->severity] ?? null;
        if ($slaHours) {
            $this->bugSlaRepo->update($id, [
                'sla_deadline' => now()->addHours($slaHours)->toDateTimeString(),
            ]);
        }
    }
}
