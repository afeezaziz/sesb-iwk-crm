<?php

namespace App\Http\Controllers;

use App\Models\CompletionRecord;
use App\Models\Process;
use App\Services\CompletionEngine;
use App\Support\Nbs;
use Illuminate\Http\Request;

class CompletionController extends Controller
{
    private function guard(Process $p): void
    {
        // gap processes are not operable in Version 1 (server-side gate)
        abort_if($p->coverage === 'gap' && Nbs::isV1(), 403,
            'This process is graded gap by IWK and is not available in Version 1.');
    }

    public function run(Request $r, Process $process, CompletionEngine $engine)
    {
        $this->guard($process);
        abort_unless(in_array($process->coverage, ['enhancement', 'gap']), 404);
        $action = $r->input('action');
        $msg = $engine->execute($process, $action, $r->all());
        return back()->with('ok', $msg);
    }

    public function decide(Request $r, CompletionRecord $record, CompletionEngine $engine)
    {
        $engine->decide($record, $r->validate(['verdict' => 'required|in:approved,rejected'])['verdict']);
        return back()->with('ok', "Record {$record->ref} {$record->status} by approver — recorded with audit.");
    }
}
