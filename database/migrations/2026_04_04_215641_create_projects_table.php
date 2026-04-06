<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_number')->unique();
            $table->string('title');
            $table->foreignId('country_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('organization_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('funder_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->decimal('approved_amount', 14, 2)->default(0);
            $table->enum('state', [
                'new',
                'pending_readiness',
                'ready_for_execution',
                'in_execution',
                'pending_documentation',
                'delayed',
                'completed',
                'closed',
            ])->default('new');
            $table->enum('documentation_status', ['not_started', 'partial', 'complete'])->default('not_started');
            $table->enum('financial_status', ['unfunded', 'partially_funded', 'funded', 'partially_spent', 'settled'])->default('unfunded');
            $table->text('readiness_notes')->nullable();
            $table->text('execution_notes')->nullable();
            $table->text('documentation_notes')->nullable();
            $table->longText('final_report')->nullable();
            $table->string('photo_album_url')->nullable();
            $table->string('video_album_url')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'documentation_status', 'financial_status']);
            $table->index('expected_end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
