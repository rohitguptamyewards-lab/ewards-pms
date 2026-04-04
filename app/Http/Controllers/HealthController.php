<?php

namespace App\Http\Controllers;

use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $dbStatus = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'error';
        }

        $status = $dbStatus === 'ok' ? 'healthy' : 'unhealthy';

        return response()->json([
            'status' => $status,
            'db' => $dbStatus,
            'timestamp' => now()->toIso8601String(),
        ], $status === 'healthy' ? 200 : 503);
    }

    /**
     * Diagnostic: test the exact welcome email flow.
     */
    public function debugEmail(): JsonResponse
    {
        $to = 'rohit20092004@gmail.com';
        $mailConfig = [
            'mailer'   => config('mail.default'),
            'host'     => config('mail.mailers.smtp.host'),
            'port'     => config('mail.mailers.smtp.port'),
            'scheme'   => config('mail.mailers.smtp.scheme'),
            'username' => config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET',
            'password' => config('mail.mailers.smtp.password') ? 'SET' : 'NOT SET',
            'from'     => config('mail.from.address'),
        ];

        try {
            $emailService = app(EmailNotificationService::class);
            $emailService->onTeamMemberCreated($to, 'Debug User', 'developer', 'TestPass123');
            return response()->json(['status' => 'sent', 'to' => $to, 'config' => $mailConfig]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'failed',
                'error'   => $e->getMessage(),
                'trace'   => array_slice(explode("\n", $e->getTraceAsString()), 0, 5),
                'config'  => $mailConfig,
            ], 500);
        }
    }
}
