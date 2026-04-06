<?php

namespace App\Services;

use App\Mail\PmsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Get the base URL for action links in emails.
     */
    private function baseUrl(): string
    {
        return rtrim(config('app.url'), '/');
    }

    /**
     * Send notification to specific user IDs.
     */
    public function notifyUsers(array $userIds, string $subject, string $heading, string $body, ?string $actionUrl = null, ?string $actionLabel = null): void
    {
        if (empty($userIds)) {
            return;
        }

        $emails = DB::table('team_members')
            ->whereIn('id', $userIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('email')
            ->toArray();

        $this->sendToEmails($emails, $subject, $heading, $body, $actionUrl, $actionLabel);
    }

    /**
     * Send notification to all managers (CTO, CEO, Manager roles).
     */
    public function notifyManagers(string $subject, string $heading, string $body, ?string $actionUrl = null, ?string $actionLabel = null): void
    {
        $emails = DB::table('team_members')
            ->whereIn('role', ['cto', 'ceo', 'manager'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('email')
            ->toArray();

        $this->sendToEmails($emails, $subject, $heading, $body, $actionUrl, $actionLabel);
    }

    /**
     * Send notification to all members of a project.
     */
    public function notifyProjectMembers(int $projectId, string $subject, string $heading, string $body, ?string $actionUrl = null, ?string $actionLabel = null, ?int $excludeUserId = null): void
    {
        $query = DB::table('project_members')
            ->join('team_members', 'project_members.user_id', '=', 'team_members.id')
            ->where('project_members.project_id', $projectId)
            ->where('team_members.is_active', true)
            ->whereNull('team_members.deleted_at');

        if ($excludeUserId) {
            $query->where('team_members.id', '!=', $excludeUserId);
        }

        $emails = $query->pluck('team_members.email')->toArray();

        // Also notify the project owner
        $ownerEmail = DB::table('projects')
            ->join('team_members', 'projects.owner_id', '=', 'team_members.id')
            ->where('projects.id', $projectId)
            ->where('team_members.is_active', true)
            ->when($excludeUserId, fn($q) => $q->where('team_members.id', '!=', $excludeUserId))
            ->value('team_members.email');

        if ($ownerEmail && !in_array($ownerEmail, $emails)) {
            $emails[] = $ownerEmail;
        }

        $this->sendToEmails($emails, $subject, $heading, $body, $actionUrl, $actionLabel);
    }

    /**
     * Send emails to a list of email addresses.
     */
    private function sendToEmails(array $emails, string $subject, string $heading, string $body, ?string $actionUrl, ?string $actionLabel): void
    {
        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new PmsNotification(
                    subject_line: $subject,
                    heading: $heading,
                    body: $body,
                    actionUrl: $actionUrl,
                    actionLabel: $actionLabel,
                ));
            } catch (\Throwable $e) {
                Log::warning('Failed to send PMS notification email', [
                    'to'    => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // ─── Convenience Methods ────────────────────────────────────

    public function onTaskStatusChanged(int $projectId, int $taskId, string $oldStatus, string $newStatus): void
    {
        $task = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select('tasks.title', 'team_members.name as assignee_name', 'projects.name as project_name')
            ->where('tasks.id', $taskId)
            ->first();

        if (!$task) return;

        $subject = "Task Status Changed: {$task->title}";
        $heading = "Task Status Updated";
        $body = "Task \"{$task->title}\" in project \"{$task->project_name}\" has been moved from " . ucfirst(str_replace('_', ' ', $oldStatus)) . " to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".";

        if ($task->assignee_name) {
            $body .= "\nAssigned to: {$task->assignee_name}";
        }

        $url = $this->baseUrl() . "/tasks/{$taskId}";
        $this->notifyProjectMembers($projectId, $subject, $heading, $body, $url, 'View Task');
    }

    public function onWorkLogCreated(int $projectId, int $userId, float $hoursSpent): void
    {
        $user = DB::table('team_members')->where('id', $userId)->value('name');
        $project = DB::table('projects')->where('id', $projectId)->value('name');

        $subject = "Work Log: {$user} logged {$hoursSpent}h on {$project}";
        $heading = "New Work Log Entry";
        $body = "{$user} logged {$hoursSpent} hours on project \"{$project}\".";

        $url = $this->baseUrl() . "/work-logs";
        $this->notifyManagers($subject, $heading, $body, $url, 'View Work Logs');
    }

    public function onRequestCreated(int $requestId, string $title, string $urgency): void
    {
        $subject = "New Request: {$title}";
        $heading = "New Merchant Request Submitted";
        $body = "A new request has been submitted: \"{$title}\"\nUrgency: " . ucfirst(str_replace('_', ' ', $urgency));

        $url = $this->baseUrl() . "/requests/{$requestId}";

        // Notify MC Team + Managers
        $emails = DB::table('team_members')
            ->whereIn('role', ['cto', 'ceo', 'manager', 'mc_team'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('email')
            ->toArray();

        $this->sendToEmails($emails, $subject, $heading, $body, $url, 'View Request');
    }

    public function onCommentAdded(string $entityType, int $entityId, int $authorId, string $commentBody, array $mentionedUserIds = []): void
    {
        $author = DB::table('team_members')->where('id', $authorId)->value('name');
        $entityLabel = ucfirst($entityType);

        $subject = "New Comment by {$author} on {$entityLabel} #{$entityId}";
        $heading = "New Comment";
        $body = "{$author} commented on {$entityLabel} #{$entityId}:\n\n\"{$commentBody}\"";

        $url = $this->baseUrl() . "/{$entityType}s/{$entityId}";

        // Notify mentioned users
        if (!empty($mentionedUserIds)) {
            $this->notifyUsers($mentionedUserIds, "[Mention] {$subject}", "You were mentioned", $body, $url, "View {$entityLabel}");
        }

        // Also notify based on entity type
        if ($entityType === 'project') {
            $this->notifyProjectMembers($entityId, $subject, $heading, $body, $url, "View {$entityLabel}", $authorId);
        }
    }

    public function onFeatureStatusChanged(int $featureId, string $oldStatus, string $newStatus, int $changedById): void
    {
        $feature = DB::table('features')
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->select('features.title', 'features.assigned_to', 'features.qa_owner_id', 'modules.name as module_name')
            ->where('features.id', $featureId)
            ->first();

        if (!$feature) return;

        $changedBy = DB::table('team_members')->where('id', $changedById)->value('name');

        $subject = "Feature Status: {$feature->title} -> " . ucfirst(str_replace('_', ' ', $newStatus));
        $heading = "Feature Status Updated";
        $body = "Feature \"{$feature->title}\" has been moved from " . ucfirst(str_replace('_', ' ', $oldStatus)) . " to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".\nUpdated by: {$changedBy}";

        $url = $this->baseUrl() . "/features/{$featureId}";

        $notifyIds = array_filter([$feature->assigned_to, $feature->qa_owner_id]);
        $notifyIds = array_diff($notifyIds, [$changedById]);

        if (!empty($notifyIds)) {
            $this->notifyUsers($notifyIds, $subject, $heading, $body, $url, 'View Feature');
        }

        // Also notify managers
        $this->notifyManagers($subject, $heading, $body, $url, 'View Feature');
    }

    /**
     * Notify a user when they are assigned to a feature.
     */
    public function onFeatureAssigned(int $featureId, int $assigneeId, int $assignedById): void
    {
        if ($assigneeId === $assignedById) return;

        $feature = DB::table('features')->where('id', $featureId)->first();
        $assignedBy = DB::table('team_members')->where('id', $assignedById)->value('name');

        if (!$feature) return;

        $subject = "Feature Assigned: {$feature->title}";
        $heading = "You've Been Assigned a Feature";
        $body = "You have been assigned to work on the feature \"{$feature->title}\".\nAssigned by: {$assignedBy}";

        $url = $this->baseUrl() . "/features/{$featureId}";
        $this->notifyUsers([$assigneeId], $subject, $heading, $body, $url, 'View Feature');
    }

    /**
     * Notify a user when they are assigned as QA owner of a feature.
     */
    public function onFeatureQaAssigned(int $featureId, int $qaOwnerId, int $assignedById): void
    {
        if ($qaOwnerId === $assignedById) return;

        $feature = DB::table('features')->where('id', $featureId)->first();
        $assignedBy = DB::table('team_members')->where('id', $assignedById)->value('name');

        if (!$feature) return;

        $subject = "QA Assignment: {$feature->title}";
        $heading = "You've Been Assigned as QA Owner";
        $body = "You have been assigned as the QA owner for feature \"{$feature->title}\".\nAssigned by: {$assignedBy}";

        $url = $this->baseUrl() . "/features/{$featureId}";
        $this->notifyUsers([$qaOwnerId], $subject, $heading, $body, $url, 'View Feature');
    }

    /**
     * Notify a user when a task is assigned to them.
     */
    public function onTaskAssigned(int $taskId, int $assigneeId, int $assignedById): void
    {
        if ($assigneeId === $assignedById) return;

        $task = DB::table('tasks')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select('tasks.title', 'projects.name as project_name')
            ->where('tasks.id', $taskId)
            ->first();
        $assignedBy = DB::table('team_members')->where('id', $assignedById)->value('name');

        if (!$task) return;

        $subject = "Task Assigned: {$task->title}";
        $heading = "You've Been Assigned a Task";
        $body = "You have been assigned the task \"{$task->title}\"";
        if ($task->project_name) {
            $body .= " in project \"{$task->project_name}\"";
        }
        $body .= ".\nAssigned by: {$assignedBy}";

        $url = $this->baseUrl() . "/tasks/{$taskId}";
        $this->notifyUsers([$assigneeId], $subject, $heading, $body, $url, 'View Task');
    }

    public function onProjectMemberAdded(int $projectId, int $userId): void
    {
        $project = DB::table('projects')->where('id', $projectId)->value('name');
        $user = DB::table('team_members')->where('id', $userId)->first();

        if (!$user || !$project) return;

        $subject = "You've been added to project: {$project}";
        $heading = "Project Assignment";
        $body = "You have been added as a member of the project \"{$project}\".";

        $url = $this->baseUrl() . "/projects/{$projectId}";
        $this->notifyUsers([$userId], $subject, $heading, $body, $url, 'View Project');
    }

    /**
     * Notify the requester when their request needs clarification.
     */
    public function onRequestClarificationNeeded(int $requestId, ?string $reason = null): void
    {
        $request = DB::table('requests')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->select('requests.title', 'requests.requested_by', 'team_members.email', 'team_members.name')
            ->where('requests.id', $requestId)
            ->first();

        if (!$request || !$request->email) return;

        $subject = "Clarification Needed: {$request->title}";
        $heading = "Clarification Required on Your Request";
        $body = "Your request \"{$request->title}\" needs clarification from the team.";
        if ($reason) {
            $body .= "\n\nDetails: {$reason}";
        }
        $body .= "\n\nPlease add a comment on the request to provide the required information.";

        $url = $this->baseUrl() . "/requests/{$requestId}";
        $this->sendToEmails([$request->email], $subject, $heading, $body, $url, 'View Request');
    }

    /**
     * Notify the requester when their request is triaged (accepted/rejected/deferred).
     */
    public function onRequestTriaged(int $requestId, string $action, ?string $reason = null): void
    {
        $request = DB::table('requests')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->select('requests.title', 'requests.requested_by', 'team_members.email', 'team_members.name')
            ->where('requests.id', $requestId)
            ->first();

        if (!$request || !$request->email) return;

        $actionLabel = match ($action) {
            'accept' => 'Accepted',
            'reject' => 'Rejected',
            'defer'  => 'Deferred',
            default  => ucfirst($action),
        };

        $subject = "Request {$actionLabel}: {$request->title}";
        $heading = "Your Request Has Been {$actionLabel}";
        $body = "Your request \"{$request->title}\" has been {$actionLabel}.";
        if ($reason) {
            $body .= "\n\nReason: {$reason}";
        }

        $url = $this->baseUrl() . "/requests/{$requestId}";
        $this->sendToEmails([$request->email], $subject, $heading, $body, $url, 'View Request');
    }

    /**
     * Send welcome email to a newly created team member with their credentials.
     */
    public function onTeamMemberCreated(string $email, string $name, string $role, string $plainPassword): void
    {
        $roleLabel = ucfirst(str_replace('_', ' ', $role));
        $loginUrl = $this->baseUrl() . '/login';
        $docsUrl = $this->baseUrl() . '/documentation';

        $subject = "Welcome to eWards PMS - Your Account is Ready";
        $heading = "Welcome to eWards PMS, {$name}!";
        $body = "Your account has been created on eWards Project Management System.\n\n"
            . "Here are your login credentials:\n"
            . "Email: {$email}\n"
            . "Password: {$plainPassword}\n"
            . "Role: {$roleLabel}\n\n"
            . "Please change your password after your first login.\n\n"
            . "You can also view the complete project documentation here:\n{$docsUrl}";

        $this->sendToEmails([$email], $subject, $heading, $body, $loginUrl, 'Login to eWards PMS');
    }

    /**
     * Item 32 — Weekly report email for sales/managers.
     */
    public function sendWeeklyReport(object $member, array $report, \Illuminate\Support\Carbon $from, \Illuminate\Support\Carbon $to): void
    {
        $fromLabel = $from->format('M d');
        $toLabel   = $to->format('M d, Y');
        $shipped   = count($report['features_shipped'] ?? []);
        $newReqs   = count($report['new_requests'] ?? []);
        $releases  = count($report['releases'] ?? []);

        $subject = "Weekly Product Summary: {$fromLabel} – {$toLabel}";
        $heading = "Weekly Product Update";
        $body    = "Hi {$member->name},\n\nHere's your weekly product summary for {$fromLabel} – {$toLabel}:\n\n"
                 . "• Features shipped: {$shipped}\n"
                 . "• New requests received: {$newReqs}\n"
                 . "• Requests triaged: {$report['requests_triaged']}\n"
                 . "• Team hours logged: {$report['hours_logged']}h\n"
                 . "• Critical bugs open: {$report['critical_bugs']}\n"
                 . "• Releases: {$releases}\n\n"
                 . "Data as of: {$report['data_freshness']}";

        $url = $this->baseUrl() . '/features';
        $this->sendToEmails([$member->email], $subject, $heading, $body, $url, 'View Pipeline');
    }

    /**
     * Item 33 — Monthly report email for CEO/CTO.
     */
    public function sendMonthlyReport(object $member, array $report, \Illuminate\Support\Carbon $from, \Illuminate\Support\Carbon $to): void
    {
        $monthLabel = $from->format('F Y');

        $subject = "Monthly Executive Summary: {$monthLabel}";
        $heading = "Monthly Executive Report — {$monthLabel}";
        $body    = "Hi {$member->name},\n\n"
                 . ($report['narrative'] ?? '') . "\n\n"
                 . "FEATURE PIPELINE:\n"
                 . "• In Progress: " . ($report['pipeline']['in_progress'] ?? 0) . "\n"
                 . "• In Review: "   . ($report['pipeline']['in_review']   ?? 0) . "\n"
                 . "• In QA: "       . ($report['pipeline']['in_qa']       ?? 0) . "\n"
                 . "• Released: "    . ($report['pipeline']['released']    ?? 0) . "\n"
                 . "• Backlog: "     . ($report['pipeline']['backlog']     ?? 0) . "\n\n"
                 . "TEAM PERFORMANCE:\n"
                 . "• Total hours logged: {$report['hours_logged']}h\n"
                 . "• Team size: {$report['team_size']}\n"
                 . "• Active projects: {$report['active_projects']}\n\n"
                 . "Data as of: {$report['data_freshness']}";

        $url = $this->baseUrl() . '/dashboard/ceo';
        $this->sendToEmails([$member->email], $subject, $heading, $body, $url, 'View CEO Dashboard');
    }

    /**
     * Item 34 — Quarterly deep-dive report email for CTO/CEO.
     */
    public function sendQuarterlyReport(object $member, array $report, \Illuminate\Support\Carbon $from, \Illuminate\Support\Carbon $to): void
    {
        $subject = "Quarterly Deep-Dive: {$report['quarter']}";
        $heading = "Quarterly Report — {$report['quarter']}";

        $body = "Hi {$member->name},\n\nHere is your quarterly deep-dive for {$report['quarter']}:\n\n"
            . "HIGHLIGHTS:\n"
            . "• Features shipped: {$report['features_shipped']}\n"
            . "• Total investment: Rs. " . number_format($report['total_cost'], 0) . "\n"
            . "• Hours logged: {$report['hours_logged']}h\n\n";

        if (!empty($report['initiative_metrics'])) {
            $body .= "INITIATIVE ROI:\n";
            foreach (array_slice($report['initiative_metrics'], 0, 5) as $i) {
                $roi = $i->total_cost > 0 ? round($i->revenue / $i->total_cost, 1) . 'x' : 'N/A';
                $body .= "• {$i->title}: Rs. " . number_format($i->total_cost, 0) . " cost, {$i->features_released}/{$i->feature_count} shipped, ROI: {$roi}\n";
            }
            $body .= "\n";
        }

        if ($report['bug_stats']) {
            $body .= "BUG SUMMARY:\n"
                . "• Total bugs: {$report['bug_stats']['total']}\n"
                . "• SLA breached: {$report['bug_stats']['breached']}\n\n";
        }

        $body .= "Data as of: {$report['data_freshness']}";

        $url = $this->baseUrl() . '/dashboard/ceo';
        $this->sendToEmails([$member->email], $subject, $heading, $body, $url, 'View Dashboard');
    }

    /**
     * Item 35 — Personal weekly summary email for each team member.
     */
    public function sendPersonalWeeklySummary(object $member, array $summary, \Illuminate\Support\Carbon $from, \Illuminate\Support\Carbon $to): void
    {
        $fromLabel = $from->format('M d');
        $toLabel   = $to->format('M d, Y');

        $subject = "Your Week in Review: {$fromLabel} – {$toLabel}";
        $heading = "Personal Weekly Summary";

        $body = "Hi {$member->name},\n\nHere's your week in review:\n\n"
            . "• Hours logged: {$summary['hours_logged']}h\n"
            . "• Tasks completed: {$summary['tasks_completed']}\n"
            . "• Features worked on: {$summary['features_worked']}\n"
            . "• Activity log entries: {$summary['activity_log_count']}\n";

        if ($summary['streak'] > 0) {
            $body .= "• Logging streak: {$summary['streak']} days\n";
        }

        if (!empty($summary['upcoming_deadlines'])) {
            $body .= "\nUPCOMING DEADLINES:\n";
            foreach ($summary['upcoming_deadlines'] as $d) {
                $body .= "• {$d->feature_title} — due {$d->due_date} ({$d->state})\n";
            }
        }

        if (!empty($summary['blockers'])) {
            $body .= "\nACTIVE BLOCKERS:\n";
            foreach ($summary['blockers'] as $b) {
                $body .= "• {$b->feature_title}: {$b->description}\n";
            }
        }

        $url = $this->baseUrl() . '/personal/history';
        $this->sendToEmails([$member->email], $subject, $heading, $body, $url, 'View Your Dashboard');
    }

    public function onBugSlaCreated(int $featureId, string $severity): void
    {
        $feature = DB::table('features')
            ->select('title', 'assigned_to', 'qa_owner_id')
            ->where('id', $featureId)
            ->first();

        if (!$feature) return;

        $slaHours = match ($severity) {
            'p0' => '4 hours',
            'p1' => '24 hours',
            'p2' => '72 hours',
            'p3' => 'Next Sprint',
            default => 'Unknown',
        };

        $subject = "[{$severity}] Bug SLA: {$feature->title}";
        $heading = "Bug SLA Alert - " . strtoupper($severity);
        $body = "A {$severity} bug has been reported for feature \"{$feature->title}\".\nSLA Deadline: {$slaHours}\n\nPlease prioritize this fix.";

        $url = $this->baseUrl() . "/bug-sla";

        $notifyIds = array_filter([$feature->assigned_to, $feature->qa_owner_id]);
        $this->notifyUsers($notifyIds, $subject, $heading, $body, $url, 'View Bug SLA');
        $this->notifyManagers($subject, $heading, $body, $url, 'View Bug SLA');
    }
}
