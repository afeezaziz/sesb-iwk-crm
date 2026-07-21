<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic process engine. Every one of the 594 Appendix-11 processes becomes
 * genuinely operable: type-appropriate records and runs that PERSIST and are
 * auditable. Bespoke domain rules live in the featured journeys + are authored
 * per-URS; this engine gives every catalogue process real create/search/run/
 * approve behaviour so a human can test any line item end to end.
 */
return new class extends Migration
{
    public function up(): void
    {
        // record store for form / enquiry / listing / approval processes
        Schema::create('process_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $t->string('reference');                 // generated per-process ref
            $t->string('account_no')->nullable();
            $t->decimal('amount', 12, 2)->nullable();
            $t->date('effective_date')->nullable();
            $t->json('payload')->nullable();          // remaining type fields
            $t->string('status')->default('active');  // active | pending | approved | rejected
            $t->string('created_by')->default('officer-01');
            $t->string('decided_by')->nullable();
            $t->timestamps();
            $t->index(['process_id', 'status']);
        });

        // run log for batch / file processes
        Schema::create('process_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $t->string('kind');                       // batch | file
            $t->json('params')->nullable();
            $t->unsignedInteger('records_affected')->default(0);
            $t->string('status')->default('completed');
            $t->text('log')->nullable();
            $t->string('ran_by')->default('officer-01');
            $t->timestamp('ran_at');
            $t->timestamps();
            $t->index(['process_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_runs');
        Schema::dropIfExists('process_records');
    }
};
