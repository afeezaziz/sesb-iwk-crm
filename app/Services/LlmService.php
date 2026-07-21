<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Optional LLM layer. The whole AI module is designed to run WITHOUT any API
 * key (deployable on Coolify with zero secrets) — every AI feature has a
 * deterministic, on-device fallback. When ANTHROPIC_API_KEY is set, the same
 * features light up with natural-language generation.
 *
 * This mirrors the production posture the bid proposes: models run against an
 * on-prem / private-cloud endpoint, confidential data is redacted before it
 * ever leaves the process, and every call is auditable. Never send raw PII.
 */
class LlmService
{
    public function enabled(): bool
    {
        return filled(config('nbs.ai.api_key'));
    }

    /**
     * Ask the model for a short completion. Returns null if disabled or on any
     * error — callers MUST have a deterministic fallback and treat AI as
     * augmentation, never a hard dependency.
     */
    public function complete(string $system, string $prompt, int $maxTokens = 700): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $res = Http::withHeaders([
                'x-api-key'         => config('nbs.ai.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post(rtrim(config('nbs.ai.base_url'), '/') . '/v1/messages', [
                'model'      => config('nbs.ai.model'),
                'max_tokens' => $maxTokens,
                'system'     => $system,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (! $res->successful()) {
                return null;
            }
            return $res->json('content.0.text');
        } catch (\Throwable $e) {
            return null; // graceful: fall back to deterministic output
        }
    }

    /** Redact obvious PII before any egress (RFP §5 confidentiality). */
    public function redact(string $text): string
    {
        $text = preg_replace('/\b\d{6}-\d{2}-\d{4}\b/', '[IC-REDACTED]', $text);        // MyKad
        $text = preg_replace('/\b88-\d{6}-\d\b/', '[ACCT-REDACTED]', $text);            // account no.
        $text = preg_replace('/\b[\w.+-]+@[\w-]+\.[\w.-]+\b/', '[EMAIL-REDACTED]', $text);
        $text = preg_replace('/\b(?:\+?6?01)\d[- ]?\d{3,4}[- ]?\d{4}\b/', '[PHONE-REDACTED]', $text);
        return $text;
    }
}
