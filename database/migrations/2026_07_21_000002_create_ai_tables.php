<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Human-in-the-loop review of AI-proposed anomaly flags.
        // The model proposes; a named officer confirms or dismisses.
        Schema::create('anomaly_reviews', function (Blueprint $t) {
            $t->id();
            $t->string('subject_type');     // bill | adjustment
            $t->unsignedBigInteger('subject_id');
            $t->string('rule');             // detector rule key
            $t->string('verdict');          // confirmed | dismissed
            $t->string('reviewer')->default('billing-officer-01');
            $t->text('note')->nullable();
            $t->timestamps();
            $t->unique(['subject_type', 'subject_id', 'rule']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_reviews');
    }
};
