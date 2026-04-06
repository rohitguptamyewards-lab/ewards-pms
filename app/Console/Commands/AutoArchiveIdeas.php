<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 64 — Idea auto-archive (90 days with no activity → archived).
 */
class AutoArchiveIdeas extends Command
{
    protected $signature   = 'pms:auto-archive-ideas {--days=90 : Days of inactivity before archiving}';
    protected $description = 'Archive ideas that have had no activity for 90+ days';

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $threshold = Carbon::now()->subDays($days);
        $archived  = 0;

        $staleIdeas = DB::table('ideas')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['archived', 'approved', 'rejected'])
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($staleIdeas as $idea) {
            // Check for recent comments
            $lastComment = DB::table('comments')
                ->where('commentable_type', 'idea')
                ->where('commentable_id', $idea->id)
                ->whereNull('deleted_at')
                ->max('created_at');

            $lastActivity = $lastComment
                ? max(Carbon::parse($idea->updated_at), Carbon::parse($lastComment))
                : Carbon::parse($idea->updated_at);

            if ($lastActivity->lessThan($threshold)) {
                DB::table('ideas')
                    ->where('id', $idea->id)
                    ->update([
                        'status'     => 'archived',
                        'updated_at' => now(),
                    ]);

                // Notify idea creator
                if ($idea->created_by ?? null) {
                    DB::table('pms_notifications')->insert([
                        'notifiable_type' => 'team_member',
                        'notifiable_id'   => $idea->created_by,
                        'type'            => 'idea_auto_archived',
                        'data'            => json_encode([
                            'idea_id'    => $idea->id,
                            'idea_title' => $idea->title,
                            'message'    => "Idea \"{$idea->title}\" was auto-archived after {$days} days of inactivity.",
                            'days'       => $days,
                        ]),
                        'channel'      => 'in_app',
                        'is_critical'  => false,
                        'scheduled_for' => now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }

                $archived++;
            }
        }

        $this->info("Auto-archived {$archived} ideas.");
        Log::info("pms:auto-archive-ideas completed. Archived: {$archived}");

        return self::SUCCESS;
    }
}
