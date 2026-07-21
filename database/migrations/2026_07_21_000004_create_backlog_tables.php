<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Appendix 10 backlog (242 JIRA items) for the AI Assessment Triage feature.
 * The model PROPOSES a classification; a named engineer CONFIRMS or OVERRIDES.
 * Only confirmed/overridden items count as scope (RFP §7.2.3 System Assessment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backlog_items', function (Blueprint $t) {
            $t->id();
            $t->string('jira_key')->nullable();      // 6 items have none (clarification C-1)
            $t->string('module_code', 10)->index();
            $t->string('title');
            $t->string('jira_status')->nullable();
            $t->string('classification')->nullable(); // missing_process | enhancement | defect | net_new
            $t->unsignedTinyInteger('confidence')->nullable();
            $t->text('rationale')->nullable();
            $t->string('triage_state')->default('pending'); // pending | confirmed | overridden
            $t->string('reviewer')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlog_items');
    }
};
