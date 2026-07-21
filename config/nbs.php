<?php

/*
 | Feature gate map — every gated feature traces to real Appendix 11 processes
 | and carries IWK's own grade. In "v1" mode: gap features are OFF (server-side),
 | enhancement features are PARTIAL. In "v2" (completed) everything is ON.
 | Do not add features here without an appendix anchor.
 */
return [

    'features' => [
        'adjustments' => [
            'grade' => 'enhancement',
            'label' => 'Account Adjustments',
            'processes' => ['RTMADJS' => 'Account Adjustments', 'IWKSUMADJ' => 'Account Summary Adjustments'],
        ],
        'refunds' => [
            'grade' => 'gap',
            'label' => 'Refunds — Water Credit',
            'processes' => ['IWKREFUND' => 'Account Refunds', 'IWKREFCON' => 'Refund Controls'],
        ],
        'ebilling' => [
            'grade' => 'gap',
            'label' => 'E-Billing Controls',
            'processes' => ['IWKEBILLREBCAN' => 'E-Billing Rebate Cancel', 'IWKEBILLAUD' => 'E-Billing Audit'],
        ],
        'name_history' => [
            'grade' => 'enhancement',
            'label' => 'Name & Address History',
            'processes' => ['RTENAHIST' => 'Name & Address History'],
        ],
        'batch_control' => [
            'grade' => 'gap',
            'label' => 'Batch Control & Receipt Checking',
            'processes' => ['IWKBATCH.CHECK' => 'Check/Regen Batch Control', 'CS5CRCHK' => 'Check Cash Receipts', 'CSGBDAY' => 'Rebuild Daily Unposted'],
        ],
        'daily_summary' => [
            'grade' => 'gap',
            'label' => 'Daily Receipts Summaries',
            'processes' => ['CS.TYPES' => 'Daily Receipts by Type', 'CS.FUNDS' => 'Daily Receipts by Fund'],
        ],
        'dca_suite' => [
            'grade' => 'enhancement',
            'label' => 'DCA Management Suite',
            'processes' => ['IWKDCAPLGEN' => 'DCA Payment Listing Generation', 'IWKDCAPLDET' => 'DCA Payment Listing Detail', 'IWKDCAPLCOM' => 'Commission Payment Report', 'IWKDCAPERF' => 'DCA Performance Report'],
        ],
        'forecasting' => [
            'grade' => 'gap',
            'label' => 'Forecasting (entire module)',
            'processes' => ['IWKFCGEN' => 'Forecast Report Creation', 'IWKFCADJCALC' => 'Calculate Forecast Adjustment %', 'IWKFCFRZ' => 'Freeze Forecast & Calculate Revenue Data'],
        ],
    ],

    /*
     | Optional AI layer. Everything works with NO key (deterministic fallback);
     | set ANTHROPIC_API_KEY to light up natural-language generation. The bid
     | proposes these run against an on-prem/private-cloud model endpoint with
     | redaction before egress — base_url is overridable for exactly that.
     | Pitched + priced as the Appendix 3 optional line "AI Tools".
     */
    'ai' => [
        'api_key'  => env('ANTHROPIC_API_KEY'),
        'base_url' => env('NBS_AI_BASE_URL', 'https://api.anthropic.com'),
        'model'    => env('NBS_AI_MODEL', 'claude-sonnet-4-5'),
    ],
];
