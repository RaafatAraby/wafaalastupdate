<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('financial_transactions', 'sender_name')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->string('sender_name')->nullable()->after('transaction_date');
            });
        }

        if (! Schema::hasColumn('financial_transactions', 'receiver_name')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->string('receiver_name')->nullable()->after('sender_name');
            });
        }

        if (! Schema::hasColumn('financial_transactions', 'attachment_path')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_transactions', 'attachment_path')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });
        }

        if (Schema::hasColumn('financial_transactions', 'receiver_name')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropColumn('receiver_name');
            });
        }

        if (Schema::hasColumn('financial_transactions', 'sender_name')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropColumn('sender_name');
            });
        }
    }
};
