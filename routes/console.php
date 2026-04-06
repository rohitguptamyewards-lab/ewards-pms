<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─────────────────────────────────────────────────────────────────────────────
// PMS Scheduled Jobs
// ─────────────────────────────────────────────────────────────────────────────

// Item 30/31 — Dispatch batched notifications at 9 AM and 5:30 PM IST (UTC+5:30 → UTC 03:30 and 12:00)
Schedule::command('pms:dispatch-batched-notifications')->dailyAt('03:30'); // 9:00 AM IST
Schedule::command('pms:dispatch-batched-notifications')->dailyAt('12:00'); // 5:30 PM IST

// Item 27 — Deadline-based reminder notifications (7d, 3d, 1d)
Schedule::command('pms:send-deadline-reminders')->dailyAt('03:30'); // 9:00 AM IST

// Item 60 — Deadline state calculator (on_track / at_risk / breached)
Schedule::command('pms:calculate-deadline-states')->dailyAt('00:00');

// Item 28/61 — Bug SLA state calculator + escalation chain
Schedule::command('pms:calculate-bug-sla-states')->hourly();

// Item 58 — Daily activity log reminder at 5 PM IST (11:30 UTC)
Schedule::command('pms:daily-activity-reminder')->dailyAt('11:30');

// Item 59 — Stale work detection (every weekday)
Schedule::command('pms:detect-stale-work')->weekdays()->dailyAt('04:00');

// Item 32/62 — Auto-generated weekly report: every Monday 9 AM IST
Schedule::command('pms:generate-weekly-report')->weeklyOn(1, '03:30'); // Monday 9 AM IST

// Item 33/62 — Auto-generated monthly report: 1st of month 9 AM IST
Schedule::command('pms:generate-monthly-report')->monthlyOn(1, '03:30');

// Item 63 — Feature cost/usage snapshot (daily at midnight IST)
Schedule::command('pms:snapshot-feature-costs')->dailyAt('18:30'); // midnight IST

// Item 64 — Idea auto-archive (daily check)
Schedule::command('pms:auto-archive-ideas')->dailyAt('01:00');

// Item 34 — Quarterly deep-dive report: 1st of Jan/Apr/Jul/Oct at 9 AM IST
Schedule::command('pms:generate-quarterly-report')->quarterly()->at('03:30');

// Item 35 — Personal weekly summary: every Monday 9 AM IST
Schedule::command('pms:send-personal-weekly-summary')->weeklyOn(1, '03:30');

// Item 65 — Velocity forecast recalculation (every Sunday night)
Schedule::command('pms:recalculate-velocity-forecast')->weeklyOn(0, '20:00'); // Sunday 1:30 AM IST
