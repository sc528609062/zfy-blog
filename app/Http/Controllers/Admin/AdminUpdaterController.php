<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UpgradeLog;
use App\Services\SystemUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminUpdaterController extends Controller
{
    public function status(Request $request, SystemUpdateService $updates): JsonResponse
    {
        $this->authorizeUpdater($request);

        return response()->json($updates->status());
    }

    public function run(Request $request, SystemUpdateService $updates): JsonResponse
    {
        $this->authorizeUpdater($request);

        $data = $request->validate([
            'version' => ['required', 'string', 'regex:/^v\d+\.\d+\.\d+$/'],
        ]);

        try {
            $log = $updates->start($data['version']);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => '更新任务已启动', 'log' => $updates->logPayload($log)]);
    }

    public function log(Request $request, UpgradeLog $log, SystemUpdateService $updates): JsonResponse
    {
        $this->authorizeUpdater($request);

        return response()->json(['log' => $updates->logPayload($log->refresh())]);
    }

    private function authorizeUpdater(Request $request): void
    {
        $user = $request->user();

        abort_unless($user?->can('manage system') || $user?->hasAnyRole(['SUPER_ADMIN', 'ADMIN']), 403);
    }
}
