<?php

namespace App\Http\Controllers;

use App\Mail\PmsNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
     * Test email sending (temporary diagnostic endpoint).
     */
    public function testEmail(): JsonResponse
    {
        try {
            $to = config('mail.from.address', 'project.tool.ewards@gmail.com');
            Mail::to($to)->send(new PmsNotification(
                subject_line: 'eWards PMS - Email Test',
                heading: 'Email Works!',
                body: "If you received this, email sending is working correctly on the live server.\n\nTimestamp: " . now()->toIso8601String(),
                actionUrl: config('app.url'),
                actionLabel: 'Open PMS',
            ));

            return response()->json(['status' => 'sent', 'to' => $to]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'failed', 'error' => $e->getMessage()], 500);
        }
    }
}
