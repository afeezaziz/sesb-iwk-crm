<?php

namespace App\Services;

use App\Models\Process;

/**
 * AI URS / Gap Drafter.
 *
 * The single most on-thesis AI feature: the entire engagement is scored on
 * authoring URS documentation (Appendix 1 §I; RFP §7.2.4 — "the tenderer shall
 * undertake the development and documentation of requirements for any modules
 * that remain undefined or undeveloped"). This drafts a first-cut URS section
 * for any gap/enhancement process, from its Appendix-11 metadata and the RFP's
 * own module descriptions — turning a 90-item gap list into review-ready drafts
 * in minutes instead of weeks.
 *
 * With no API key it emits a structured deterministic skeleton (headings,
 * scope, functional requirements seeded from the process family, acceptance
 * criteria, integration + data + audit sections). With a key, the LLM fills
 * richer functional requirements. Output is always a DRAFT for a human BA to
 * refine — never auto-final.
 */
class UrsDrafterService
{
    public function __construct(private LlmService $llm) {}

    public function draft(Process $p): array
    {
        $deterministic = $this->skeleton($p);

        $llmBody = $this->llm->complete(
            "You are a senior business analyst authoring a User Requirement Specification (URS) for IWK's sewerage Billing & CRM system (ASP.NET/React/Oracle 19c). Given a legacy process, output 5-8 concise, testable functional requirements as 'FR-n: The system shall ...' lines. No preamble, no headings, requirements only.",
            $this->llm->redact("Module: {$p->module_code}\nLegacy process: {$p->legacy}\nDescription: {$p->description}\nIWK grade: {$p->coverage}"),
            520
        );

        if ($llmBody) {
            $deterministic['functional'] = array_values(array_filter(array_map('trim', explode("\n", $llmBody))));
            $deterministic['source'] = 'llm';
        }
        return $deterministic;
    }

    private function skeleton(Process $p): array
    {
        $fam = $this->family($p);
        return [
            'source' => 'deterministic',
            'title'  => "URS — {$p->description}",
            'ref'    => "URS-{$p->module_code}-{$p->legacy}",
            'module' => $p->module_code,
            'grade'  => $p->coverage,
            'purpose' => "Define the business, functional, integration, security and compliance requirements for the “{$p->description}” process ({$p->legacy}), graded {$p->coverage} in IWK Appendix 11 and requiring "
                . ($p->coverage === 'gap' ? 'full build (no equivalent exists in the new system).' : 'enhancement to close the shortfall against the finalised requirements.'),
            'scope' => [
                "In scope: the “{$p->description}” capability within the {$this->moduleName($p->module_code)} module.",
                "Data validation, controlled access (role-based), historical records and full audit trail.",
                "Alignment with the existing ASP.NET/ReactJS front-end and Oracle 19c data model.",
            ],
            'functional' => $fam,
            'integration' => $this->integration($p),
            'nonfunctional' => [
                'Performance: screen actions respond within 3s at peak concurrency.',
                'Security: authenticated, role-restricted; changes captured with user + timestamp.',
                'Auditability: every create/update/approve recorded and reportable.',
                'Data protection: compliant with IWK confidentiality and PDPA obligations.',
            ],
            'acceptance' => [
                "Given a valid input, when the officer submits, then the record persists and appears in history.",
                "Given an invalid input, when submitted, then a clear validation error is shown and nothing is written.",
                "Given a completed action, when audited, then the change is traceable to a named user and time.",
            ],
            'open' => [
                "Confirm the authoritative source of truth vs. legacy BRAINS during the 4–5 Aug access window (RFP §9.3).",
                "Confirm downstream interfaces affected (SAP/GL, CEMS, Water Authorities as applicable).",
            ],
        ];
    }

    /** Seed functional requirements by process family (deterministic fallback). */
    private function family(Process $p): array
    {
        $d = strtolower($p->description . ' ' . $p->sub_module);
        if (str_contains($d, 'refund')) return [
            'FR-1: The system shall allow an officer to initiate a refund against an eligible credit balance.',
            'FR-2: The system shall enforce a maker-checker approval before a refund is posted.',
            'FR-3: The system shall validate that the refund does not exceed the available water-credit balance.',
            'FR-4: The system shall post the refund to the General Ledger via the SAP/ERPNext interface.',
            'FR-5: The system shall retain a full audit trail of refund initiation, approval and posting.',
        ];
        if (str_contains($d, 'adjust')) return [
            'FR-1: The system shall recalculate affected bills retrospectively from the adjustment’s effective date.',
            'FR-2: The system shall preview the net impact of an adjustment before it is committed.',
            'FR-3: The system shall require approval for adjustments exceeding a configurable threshold.',
            'FR-4: The system shall prevent duplicate adjustments for the same account, type and period.',
            'FR-5: The system shall record the pre- and post-adjustment values for audit.',
        ];
        if (str_contains($d, 'forecast')) return [
            'FR-1: The system shall compute account-level revenue forecasts from billed history and adjustment factors.',
            'FR-2: The system shall allow a configurable adjustment percentage per segment.',
            'FR-3: The system shall freeze a forecast and lock the underlying revenue data.',
            'FR-4: The system shall report frozen forecast revenue for the financial year.',
        ];
        if (str_contains($d, 'report') || str_contains($d, 'listing') || str_contains($d, 'summary')) return [
            'FR-1: The system shall generate the report from current billing/receipting/finance data.',
            'FR-2: The system shall allow parameterisation by period, cycle and area.',
            'FR-3: The system shall support export and scheduled generation.',
            'FR-4: The system shall confirm the reporting platform (DevExpress/Power BI) per the finalised URS.',
        ];
        if (str_contains($d, 'receipt') || str_contains($d, 'cash') || str_contains($d, 'batch')) return [
            'FR-1: The system shall capture and allocate payments against outstanding bills oldest-first.',
            'FR-2: The system shall reconcile a batch control total against the system total and report variances.',
            'FR-3: The system shall support void/reversal with full balance restoration.',
        ];
        return [
            'FR-1: The system shall allow authorised officers to create, update and view the record.',
            'FR-2: The system shall validate all mandatory fields against business rules before saving.',
            'FR-3: The system shall maintain historical records with effective dating.',
            'FR-4: The system shall restrict access by user role and record every change for audit.',
        ];
    }

    private function integration(Process $p): array
    {
        $m = $p->module_code;
        $map = [
            'GL'    => ['SAP Financial System (transaction & summary posting via ERPNext).'],
            'JOINT' => ['State Water Authorities — two-way billing/payment/adjustment file exchange.'],
            'DEBT'  => ['DCA Portal — debt assignment & collection file exchange.'],
            'CENQ'  => ['CEMS — enquiry routing and resolution round-trip.'],
            'CASH'  => ['IWK CRI (until go-live) and downstream GL posting.'],
            'CRM'   => ['IGIS (geolocation), EDMS (correspondence), PUKAL/AG for government accounts.'],
        ];
        return $map[$m] ?? ['Confirm upstream/downstream interfaces during assessment (RFP §7.2.8).'];
    }

    private function moduleName(string $c): string
    {
        return config('modules.names.' . $c, $c);
    }
}
