<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IWK NBS demonstration prototype — domain schema.
 * All data seeded into these tables is fictitious sample data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('id_no')->nullable();          // fictitious registration/IC
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->timestamps();
        });

        Schema::create('premises', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();             // PE-xxxxxx
            $t->string('address');
            $t->string('category');                   // Domestic | Commercial | Industrial
            $t->string('status')->default('connected'); // connected | vacant
            $t->timestamps();
        });

        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();               // 88-xxxxxx-x
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('premise_id')->constrained('premises');
            $t->string('category');                   // Domestic | Commercial
            $t->string('status')->default('active');
            $t->date('registered_at');
            $t->timestamps();
        });

        Schema::create('tariffs', function (Blueprint $t) {
            $t->id();
            $t->string('category')->unique();
            $t->decimal('monthly_charge', 10, 2);     // simple flat sewerage charge (sample)
            $t->timestamps();
        });

        Schema::create('bills', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();               // B-YYMM-xxxx
            $t->foreignId('account_id')->constrained();
            $t->string('period');                     // YYYY-MM
            $t->date('bill_date');
            $t->date('due_date');
            $t->decimal('amount', 10, 2);
            $t->decimal('paid', 10, 2)->default(0);
            $t->string('status')->default('unpaid');  // unpaid | partial | paid | trial
            $t->string('source')->default('seed');    // seed | billing_run | adjustment
            $t->timestamps();
            $t->index(['account_id', 'status']);
        });

        Schema::create('receipts', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();               // R-xxxxxx
            $t->foreignId('account_id')->constrained();
            $t->decimal('amount', 10, 2);
            $t->string('method');                     // cash | cheque | card | fpx
            $t->string('teller')->default('counter-05');
            $t->string('batch')->nullable();          // BC-YYMMDD-teller
            $t->string('status')->default('posted');  // posted | voided
            $t->timestamp('posted_at');
            $t->timestamps();
        });

        Schema::create('receipt_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receipt_id')->constrained();
            $t->foreignId('bill_id')->constrained();
            $t->decimal('amount', 10, 2);
            $t->timestamps();
        });

        Schema::create('adjustments', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();               // ADJ-xxxxx
            $t->foreignId('account_id')->constrained();
            $t->string('type');                       // billing | summary
            $t->decimal('amount', 10, 2);             // signed
            $t->string('reason');
            $t->date('effective_date');
            $t->string('status')->default('pending'); // pending | approved | applied | rejected
            $t->string('raised_by')->default('counter-05');
            $t->string('approved_by')->nullable();
            $t->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();               // ENQ-xxxxx
            $t->foreignId('account_id')->nullable()->constrained();
            $t->string('channel');                    // counter | call | portal | email
            $t->string('category');
            $t->text('detail')->nullable();
            $t->string('status')->default('open');    // open | with_cems | pending_info | resolved
            $t->string('assigned_to')->nullable();
            $t->date('sla_due');
            $t->timestamps();
        });

        Schema::create('arrangements', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();               // AR-xxxxx
            $t->foreignId('account_id')->constrained();
            $t->decimal('total', 10, 2);
            $t->unsignedInteger('months');
            $t->date('start_date');
            $t->string('status')->default('active');  // active | completed | defaulted
            $t->timestamps();
        });

        Schema::create('instalments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('arrangement_id')->constrained();
            $t->unsignedInteger('seq');
            $t->date('due_date');
            $t->decimal('amount', 10, 2);
            $t->string('status')->default('due');     // due | paid | overdue
            $t->timestamps();
        });

        Schema::create('billing_runs', function (Blueprint $t) {
            $t->id();
            $t->string('period');                     // YYYY-MM
            $t->string('mode');                       // trial | live
            $t->unsignedInteger('accounts_billed')->default(0);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->string('run_by')->default('demo');
            $t->timestamp('ran_at');
            $t->timestamps();
        });

        Schema::create('forecast_runs', function (Blueprint $t) {
            $t->id();
            $t->string('label');                      // e.g. FY2027
            $t->decimal('adjustment_pct', 5, 2)->default(0);
            $t->boolean('frozen')->default(false);
            $t->json('series')->nullable();           // computed projection rows
            $t->timestamp('computed_at')->nullable();
            $t->timestamps();
        });

        /* the 594 Appendix 11 processes — the catalogue + feature gate source */
        Schema::create('processes', function (Blueprint $t) {
            $t->id();
            $t->string('legacy');                     // BRAINS program name
            $t->string('module_code', 10)->index();
            $t->string('sub_module')->nullable();
            $t->string('description');
            $t->string('new_process')->nullable();
            $t->string('coverage');                   // covered | enhancement | gap
            $t->string('screen_type');                // form | listing | enquiry | batch | file | approval
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['processes','forecast_runs','billing_runs','instalments','arrangements','enquiries',
                  'adjustments','receipt_allocations','receipts','bills','tariffs','accounts',
                  'premises','customers'] as $t) Schema::dropIfExists($t);
    }
};
