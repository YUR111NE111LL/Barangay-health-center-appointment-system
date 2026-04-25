<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\SystemUpdateCommandRunner;
use Illuminate\Http\RedirectResponse;

class ApplyDownloadedUpdateController extends Controller
{
    public function __invoke(SystemUpdateCommandRunner $runner): RedirectResponse
    {
        $result = $runner->run();

        return redirect()
            ->route('super-admin.updates.index')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
