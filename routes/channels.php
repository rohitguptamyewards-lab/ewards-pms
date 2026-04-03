<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    return DB::table('project_members')
            ->where('project_id', $projectId)
            ->where('user_id', $user->id)
            ->exists()
        || in_array($user->role->value ?? $user->role, ['cto', 'ceo']);
});

Broadcast::channel('requests', function ($user) {
    return in_array($user->role->value ?? $user->role, ['cto', 'ceo', 'sales']);
});
