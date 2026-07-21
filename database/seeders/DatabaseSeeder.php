<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Premise;
use App\Models\Process;
use App\Models\Tariff;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fictitious sample dataset for the demonstration prototype, at a size that
 * makes the business logic visibly real: ~220 accounts, 7 months of bills,
 * receipts posted through the real allocation service, an arrears tail for
 * debt recovery, and the full 594-process Appendix 11 catalogue.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026); // deterministic sample data

        User::firstOrCreate(['email' => 'demo@nbs.local'], ['name' => 'Demo Teller', 'password' => 'NbsDemo#2026']);

        /* processes — the real Appendix 11 rows */
        DB::table('processes')->delete();
        $rows = json_decode(file_get_contents(database_path('seed-data/processes.json')), true);
        foreach (array_chunk($rows, 200) as $chunk) {
            Process::insert(array_map(fn ($r) => $r + ['created_at' => now(), 'updated_at' => now()], $chunk));
        }

        /* tariffs — simple flat sample charges */
        foreach (['Domestic' => 8.00, 'Commercial' => 32.00, 'Industrial' => 96.00] as $cat => $rm) {
            Tariff::updateOrCreate(['category' => $cat], ['monthly_charge' => $rm]);
        }

        /* customers / premises / accounts */
        $first = ['Contoh', 'Demo', 'Sampel', 'Maju', 'Jaya', 'Sentosa', 'Harmoni', 'Sejahtera'];
        $kind  = ['Trading', 'Holdings', 'Enterprise', 'Development', 'Services', 'Resources'];
        $areas = ['Taman Contoh Indah', 'Bandar Demo Utama', 'Seksyen Contoh 7', 'Kampung Sampel', 'Presint Demo 4', 'Taman Demo Ria'];
        $people = ['Ahmad bin Hassan', 'Siti Sampel binti Ali', 'Lim Demo Wei', 'S. Kumar Contoh', 'Nurul Demo Aina', 'Tan Sampel Hock'];

        $accounts = [];
        for ($i = 0; $i < 220; $i++) {
            $isCompany = mt_rand(0, 100) < 35;
            $name = $isCompany
                ? sprintf('%s %s Sdn Bhd', $first[mt_rand(0, 7)], $kind[mt_rand(0, 5)])
                : $people[mt_rand(0, 5)] . ' (' . ($i + 1) . ')';
            $customer = Customer::create(['name' => $name, 'phone' => '01x-xxx ' . mt_rand(1000, 9999)]);
            $premise = Premise::create([
                'code' => 'PE-' . (100000 + $i),
                'address' => 'No. ' . mt_rand(1, 99) . ', Jalan Contoh ' . mt_rand(1, 12) . ', ' . $areas[mt_rand(0, 5)],
                'category' => $isCompany ? 'Commercial' : 'Domestic',
                'status' => mt_rand(0, 100) < 94 ? 'connected' : 'vacant',
            ]);
            $accounts[] = Account::create([
                'no' => sprintf('88-%06d-%d', 102000 + $i, $i % 10),
                'customer_id' => $customer->id,
                'premise_id' => $premise->id,
                'category' => $premise->category === 'Commercial' ? 'Commercial' : 'Domestic',
                'registered_at' => now()->subDays(mt_rand(200, 2600))->toDateString(),
            ]);
        }

        /* 7 months of bills, Jan–Jul 2026 */
        $tariffs = Tariff::pluck('monthly_charge', 'category');
        foreach ($accounts as $a) {
            for ($m = 1; $m <= 7; $m++) {
                $period = sprintf('2026-%02d', $m);
                Bill::create([
                    'no' => 'B-26' . sprintf('%02d', $m) . '-' . str_pad((string) $a->id, 4, '0', STR_PAD_LEFT),
                    'account_id' => $a->id,
                    'period' => $period,
                    'bill_date' => "$period-05",
                    'due_date' => date('Y-m-d', strtotime("$period-05 +30 days")),
                    'amount' => (float) $tariffs[$a->category] * (mt_rand(90, 115) / 100), // slight variance (usage-based portion, sample)
                    'status' => 'unpaid',
                ]);
            }
        }

        /* payment behaviour via the REAL allocation service:
           ~72% pay everything, ~18% lag two months, ~10% deep arrears */
        $svc = app(ReceiptService::class);
        foreach ($accounts as $i => $a) {
            $profile = mt_rand(1, 100);
            $out = $a->outstanding();
            $payFraction = $profile <= 72 ? 1.0 : ($profile <= 90 ? 0.7 : (mt_rand(0, 100) < 40 ? 0.25 : 0.0));
            if ($payFraction <= 0) continue;
            $amount = round($out * $payFraction, 2);
            if ($amount < 1) continue;
            $r = $svc->post($a, $amount, ['cash', 'cheque', 'fpx', 'card'][mt_rand(0, 3)]);
            // spread posted_at over the last 60 days so daily summaries look real
            $r->update(['posted_at' => now()->subDays(mt_rand(0, 60))->setTime(mt_rand(8, 17), mt_rand(0, 59))]);
        }

        /* open enquiries */
        $cats = ['Billing dispute', 'Refund status', 'E-bill enrolment', 'Connection request', 'Payment not reflected'];
        foreach (range(1, 9) as $i) {
            $a = $accounts[mt_rand(0, 219)];
            Enquiry::create([
                'no' => 'ENQ-' . (88100 + $i),
                'account_id' => $a->id,
                'channel' => ['counter', 'call', 'portal', 'email'][mt_rand(0, 3)],
                'category' => $cats[mt_rand(0, 4)],
                'detail' => 'Sample enquiry for demonstration.',
                'status' => ['open', 'with_cems', 'pending_info'][mt_rand(0, 2)],
                'sla_due' => now()->addDays(mt_rand(-1, 4))->toDateString(),
            ]);
        }

        /* ---- Appendix 10 backlog (242 JIRA items) — untriaged ---- */
        $backlog = json_decode(file_get_contents(database_path('seed-data/backlog.json')), true);
        foreach (array_chunk($backlog, 200) as $chunk) {
            \App\Models\BacklogItem::insert(array_map(fn ($r) => [
                'jira_key' => $r['jira_key'], 'module_code' => $r['module_code'],
                'title' => $r['title'], 'jira_status' => $r['jira_status'],
                'triage_state' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ], $chunk));
        }

        /* ---- planted SAMPLE anomalies for the AI billing-anomaly detector ----
           These are deliberately egregious sample records — the kinds of error a
           retrospective-recalculation billing engine actually produces (RFP §2,
           Risk R-01) — so the detector has genuine targets to catch alongside the
           natural statistical outliers. All fictitious, in the sample dataset. */
        $anomalyAccts = array_slice($accounts, 0, 6);
        // 1. oversized retrospective adjustment (10x a normal bill)
        \App\Models\Adjustment::create([
            'no' => 'ADJ-90001', 'account_id' => $anomalyAccts[0]->id, 'type' => 'billing',
            'amount' => 4820.00, 'reason' => 'Retrospective re-rating (sample error)',
            'effective_date' => now()->subMonths(5)->toDateString(), 'status' => 'pending',
        ]);
        // 2. duplicate adjustment pair
        foreach ([0, 1] as $k) {
            \App\Models\Adjustment::create([
                'no' => 'ADJ-9010' . $k, 'account_id' => $anomalyAccts[1]->id, 'type' => 'summary',
                'amount' => -128.50, 'reason' => 'Dispute credit (sample duplicate)',
                'effective_date' => now()->subDays(2 * $k)->toDateString(), 'status' => 'pending',
            ]);
        }
        // 3. negative bill (credit issued as a bill)
        \App\Models\Bill::create([
            'no' => 'B-2607-NEG1', 'account_id' => $anomalyAccts[2]->id, 'period' => '2026-07',
            'bill_date' => '2026-07-05', 'due_date' => '2026-08-04', 'amount' => -212.40, 'status' => 'unpaid',
        ]);
        // 4. spike bills (data-entry / rate-config error)
        foreach ([[3, 1980.00], [4, 2450.75]] as [$idx, $amt]) {
            \App\Models\Bill::create([
                'no' => 'B-2607-SPK' . $idx, 'account_id' => $anomalyAccts[$idx]->id, 'period' => '2026-07',
                'bill_date' => '2026-07-05', 'due_date' => '2026-08-04', 'amount' => $amt, 'status' => 'unpaid',
            ]);
        }
        // 5. over-allocated bill (receipt mismatch)
        \App\Models\Bill::create([
            'no' => 'B-2606-OVR1', 'account_id' => $anomalyAccts[5]->id, 'period' => '2026-06',
            'bill_date' => '2026-06-05', 'due_date' => '2026-07-05', 'amount' => 8.00, 'paid' => 88.00, 'status' => 'paid',
        ]);

        /* ---- unmatched WAN (State Water) receipts for the WAN completion family ---- */
        for ($i = 0; $i < 18; $i++) {
            \App\Models\WanReceipt::create([
                'wan_no' => 'WAN-' . (700000 + $i),
                'account_no' => $i % 4 === 0 ? $accounts[$i]->no : null,
                'amount' => round(mt_rand(1200, 48000) / 100, 2),
                'status' => $i % 4 === 0 ? 'matched' : 'unmatched',
                'received_at' => now()->subDays(mt_rand(30, 900))->toDateString(),
            ]);
        }

        $this->command?->info('Seeded: ' . count($accounts) . ' accounts · ' . Bill::count() . ' bills · '
            . \App\Models\Receipt::count() . ' receipts (real allocations) · 594 processes · 242 backlog · '
            . \App\Models\WanReceipt::where('status', 'unmatched')->count() . ' unmatched WAN receipts · 6 planted anomalies.');
    }
}
