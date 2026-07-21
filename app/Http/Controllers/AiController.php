<?php

namespace App\Http\Controllers;

use App\Models\AnomalyReview;
use App\Models\Enquiry;
use App\Models\Process;
use App\Services\AnomalyService;
use App\Services\EnquiryCopilotService;
use App\Services\LlmService;
use App\Services\UrsDrafterService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function overview(LlmService $llm)
    {
        return view('ai.overview', ['aiLive' => $llm->enabled()]);
    }

    /* ── Billing anomaly detection ─────────────────── */
    public function anomalies(AnomalyService $svc, LlmService $llm)
    {
        $flags = $svc->scan();
        return view('ai.anomalies', [
            'flags' => $flags->take(20),
            'summary' => $svc->summary($flags),
            'aiLive' => $llm->enabled(),
        ]);
    }

    public function explainAnomaly(Request $r, AnomalyService $svc)
    {
        // reason is always available; this adds the (optional) LLM explanation
        $flags = $svc->scan();
        $f = $flags->first(fn ($x) => $x['subject_type'] === $r->query('type') && (string) $x['subject_id'] === (string) $r->query('id') && $x['rule'] === $r->query('rule'));
        abort_unless($f, 404);
        return back()->with('explain_key', "{$r->query('type')}:{$r->query('id')}:{$r->query('rule')}")
                     ->with('explain_text', $svc->explain($f));
    }

    public function reviewAnomaly(Request $r)
    {
        $data = $r->validate([
            'subject_type' => 'required|in:bill,adjustment',
            'subject_id'   => 'required|integer',
            'rule'         => 'required|string',
            'verdict'      => 'required|in:confirmed,dismissed',
        ]);
        AnomalyReview::updateOrCreate(
            ['subject_type' => $data['subject_type'], 'subject_id' => $data['subject_id'], 'rule' => $data['rule']],
            ['verdict' => $data['verdict']]
        );
        return back()->with('ok', "Anomaly {$data['verdict']} by billing officer — recorded (AI proposed, human decided).");
    }

    /* ── URS / gap drafter ─────────────────────────── */
    public function urs(Request $r, UrsDrafterService $svc, LlmService $llm)
    {
        $gaps = Process::whereIn('coverage', ['gap', 'enhancement'])
            ->orderByRaw("coverage='gap' desc")->orderBy('module_code')->limit(400)->get();
        $selected = $r->query('p') ? Process::find($r->query('p')) : null;
        $draft = $selected ? $svc->draft($selected) : null;
        return view('ai.urs', compact('gaps', 'selected', 'draft') + ['aiLive' => $llm->enabled()]);
    }

    /* ── Enquiry copilot ───────────────────────────── */
    public function enquiryCopilot(Request $r, EnquiryCopilotService $svc, LlmService $llm)
    {
        $text = $r->query('text');
        $result = null;
        if ($text) {
            $stub = new Enquiry(['no' => 'ENQ-DEMO', 'channel' => 'call']);
            $result = [
                'text' => $text,
                'classify' => $svc->classify($text),
                'reply' => $svc->draftReply($stub, $text),
            ];
        }
        $samples = [
            'My bill this month is RM 240, usually I only pay RM 8. This is wrong, please check.',
            'I already paid last week but the account still shows outstanding. Payment not reflected.',
            'How do I register for e-bill / paperless billing by email?',
        ];
        return view('ai.enquiry', compact('result', 'samples') + ['aiLive' => $llm->enabled()]);
    }
}
