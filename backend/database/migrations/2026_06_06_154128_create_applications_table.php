<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone_number');
            $table->string('position');
            $table->unsignedInteger('overall_experience');
            $table->json('top_skills')->default('[]');
            $table->json('moderate_skills')->default('[]');
            $table->text('cover_letter');
            $table->enum('status', ['pending', 'shortlisted', 'rejected'])->default('pending');
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->json('heuristic_flags')->default('[]');
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
