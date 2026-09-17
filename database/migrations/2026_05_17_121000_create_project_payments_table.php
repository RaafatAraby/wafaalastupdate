<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project payment schedule: each row represents one expected payment
 * tied to a project (e.g. instalment 1 of 3). The `payments:check-due`
 * console command scans this table and fires `payment.due_reminder`
 * (or `payment.overdue`) notifications via the InternalNotifier.
 *
 * `amount` is kept in USD so it can be summed alongside the project's
 * USD-canonical financial transactions. `original_amount` + `currency`
 * preserve the value as the user entered it.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('due_date');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('original_amount', 14, 2)->nullable();
            $table->decimal('exchange_rate', 14, 6)->nullable();
            $table->string('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending | notified | paid | canceled
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payments');
    }
};
