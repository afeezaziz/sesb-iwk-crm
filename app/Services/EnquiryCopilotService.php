<?php

namespace App\Services;

use App\Models\Enquiry;

/**
 * Customer Enquiry AI Copilot.
 *
 * Maps to the Customer Enquiry module (CENQ, graded 100% available) + the CEMS
 * interface + the existing IWK Chatbot. Auto-classifies an inbound enquiry,
 * suggests a category and routing, and drafts a first response for a human
 * agent to approve — reducing handle time on a high-volume channel.
 *
 * Deterministic keyword classifier + templated reply with no API key; richer
 * drafted reply when the LLM is enabled. Always agent-approved, never
 * auto-sent — matching the human-in-the-loop posture of the whole bid.
 */
class EnquiryCopilotService
{
    public function __construct(private LlmService $llm) {}

    private array $rules = [
        'Billing dispute'       => ['dispute', 'wrong', 'too high', 'overcharge', 'incorrect', 'salah', 'tinggi'],
        'Refund status'         => ['refund', 'credit', 'return', 'bayar balik'],
        'Payment not reflected' => ['paid', 'payment', 'not reflected', 'not updated', 'bayar', 'belum'],
        'E-bill enrolment'      => ['e-bill', 'ebill', 'email bill', 'paperless', 'register'],
        'Connection request'    => ['connect', 'new account', 'sambung', 'baru'],
        'Meter / consumption'   => ['meter', 'reading', 'consumption', 'usage', 'bacaan'],
    ];

    public function classify(string $text): array
    {
        $t = strtolower($text);
        $best = 'General enquiry'; $score = 0; $hits = [];
        foreach ($this->rules as $cat => $kw) {
            $c = 0; foreach ($kw as $k) if (str_contains($t, $k)) { $c++; $hits[] = $k; }
            if ($c > $score) { $score = $c; $best = $cat; }
        }
        $confidence = min(95, 55 + $score * 15);
        $routing = in_array($best, ['Billing dispute', 'Refund status', 'Payment not reflected'])
            ? 'CEMS — Billing Zone' : ($best === 'Meter / consumption' ? 'Field Services' : 'Front Office');
        return ['category' => $best, 'confidence' => $confidence, 'routing' => $routing, 'matched' => array_slice(array_unique($hits), 0, 4)];
    }

    public function draftReply(Enquiry $e, string $text): string
    {
        $c = $this->classify($text);
        $llm = $this->llm->complete(
            "You are a courteous IWK customer-service agent. Draft a short (3-4 sentence) reply to the customer enquiry below. Acknowledge, state the next step and an indicative timeframe. Do not invent account specifics. End with 'IWK Customer Care'.",
            $this->llm->redact("Category: {$c['category']}\nEnquiry: {$text}"),
            240
        );
        if ($llm) return $llm;

        // deterministic templated reply
        $step = [
            'Billing dispute'       => 'We have logged your billing query and routed it to our billing team for verification against your account history.',
            'Refund status'         => 'We have logged your refund query; our team will verify the available credit and update you on the refund status.',
            'Payment not reflected' => 'We have logged your payment query; our team will trace the transaction and confirm posting to your account.',
            'E-bill enrolment'      => 'We have received your e-bill request and will complete the enrolment shortly.',
            'Connection request'    => 'We have received your connection request and will guide you through the next steps.',
            'Meter / consumption'   => 'We have logged your meter query and referred it to Field Services for verification.',
        ][$c['category']] ?? 'We have logged your enquiry and our team will attend to it.';

        return "Dear valued customer,\n\nThank you for contacting IWK. {$step} You can expect an update within three (3) working days, and we will notify you at your registered contact.\n\nIWK Customer Care";
    }
}
