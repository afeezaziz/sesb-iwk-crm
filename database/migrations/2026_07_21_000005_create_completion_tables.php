<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Completion layer — the 134 enhancement/gap processes are built here as real,
 * family-specific capabilities that compute from live billing data and persist
 * results. completion_records holds per-item outputs (refunds, e-invoices, DCA
 * listings, GST entries, legal actions, reminders, WAN write-offs …);
 * completion_runs logs batch/compute executions (audit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completion_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
            $t->string('family')->index();
            $t->string('ref');
            $t->string('account_no')->nullable();
            $t->decimal('amount', 12, 2)->nullable();
            $t->string('status')->default('active');   // active|pending|approved|rejected|posted|written_off|submitted|accepted|paid
            $t->json('payload')->nullable();
            $t->string('created_by')->default('officer-01');
            $t->string('decided_by')->nullable();
            $t->timestamps();
            $t->index(['family', 'status']);
        });

        Schema::create('completion_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
            $t->string('family')->index();
            $t->string('action');
            $t->json('params')->nullable();
            $t->unsignedInteger('affected')->default(0);
            $t->decimal('value', 14, 2)->default(0);
            $t->text('log')->nullable();
            $t->string('ran_by')->default('officer-01');
            $t->timestamp('ran_at');
            $t->timestamps();
        });

        // unmatched WAN receipts (joint-billing water-credit era) for the WAN family
        Schema::create('wan_receipts', function (Blueprint $t) {
            $t->id();
            $t->string('wan_no');
            $t->string('account_no')->nullable();       // null = unmatched
            $t->decimal('amount', 10, 2);
            $t->string('status')->default('unmatched');  // unmatched|matched|written_off|transferred
            $t->date('received_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wan_receipts');
        Schema::dropIfExists('completion_runs');
        Schema::dropIfExists('completion_records');
    }
};
