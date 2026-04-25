<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\GitHubReleaseSyncService;
use App\Support\SystemUpdateCommandRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class GitHubReleaseSyncController extends Controller
{
    public function __invoke(GitHubReleaseSyncService $sync, SystemUpdateCommandRunner $runner): RedirectResponse
    {
        try {
            $result = $sync->sync(Auth::id());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('super-admin.updates.index')
                ->with('error', $e->getMessage());
        }

        $commandResult = $runner->run();
        $message = $result['message'].' '.$commandResult['message'];

        return redirect()
            ->route('super-admin.updates.index')
            ->with($commandResult['ok'] ? 'success' : 'error', $message);
    }
}
