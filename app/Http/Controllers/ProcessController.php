<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\ProcessRecord;
use App\Services\ProcessEngine;
use App\Support\Nbs;
use Illuminate\Http\Request;

/**
 * Interactive catalogue processes. Every one of the 594 is operable through the
 * generic ProcessEngine, with the v1/v2 gate still enforced server-side:
 * gap processes remain non-operable in Version 1 (blocked), functional in the
 * completed view — exactly as IWK graded them.
 */
class ProcessController extends Controller
{
    private function guard(Process $p): void
    {
        // Server-side v1 gate: a gap process is not operable as-inherited.
        abort_if($p->coverage === 'gap' && Nbs::isV1(), 403,
            'This process is graded gap by IWK and is not available in Version 1.');
    }

    public function store(Request $r, Process $process, ProcessEngine $engine)
    {
        $this->guard($process);
        $data = $r->validate([
            'account_no' => 'nullable|string|max:40',
            'amount'     => 'nullable|numeric',
            'effective_date' => 'nullable|date',
            'remarks'    => 'nullable|string|max:200',
        ]);
        $rec = $engine->create($process, $data);
        $msg = $process->screen_type === 'approval'
            ? "Record {$rec->reference} submitted for approval (maker-checker)."
            : "Record {$rec->reference} saved to “{$process->description}”.";
        return back()->with('ok', $msg);
    }

    public function decide(Request $r, ProcessRecord $record, ProcessEngine $engine)
    {
        $this->guard($record->process);
        $engine->decide($record, $r->validate(['verdict' => 'required|in:approved,rejected'])['verdict']);
        return back()->with('ok', "Record {$record->reference} {$record->status} by approver — recorded with audit.");
    }

    public function run(Request $r, Process $process, ProcessEngine $engine)
    {
        $this->guard($process);
        if ($process->screen_type === 'file') {
            $params = $r->validate(['filename' => 'required|string|max:120', 'rows' => 'required|integer|min:0|max:1000000', 'validation' => 'required|string']);
            $run = $engine->uploadFile($process, $params);
        } else {
            $params = $r->validate(['period' => 'required|string|max:20', 'cycle' => 'required|string|max:40', 'mode' => 'required|string']);
            $run = $engine->runBatch($process, $params);
        }
        return back()->with('ok', $run->log);
    }

    public function search(Request $r, Process $process, ProcessEngine $engine)
    {
        $this->guard($process);
        $q = trim((string) $r->query('q'));
        return view('process', [
            'p' => $process,
            'searchResults' => $q ? $engine->search($process, $q) : null,
            'searchTerm' => $q,
        ]);
    }
}
