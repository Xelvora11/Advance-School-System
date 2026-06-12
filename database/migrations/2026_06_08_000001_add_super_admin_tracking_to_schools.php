<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('manual_payment_status')->default('trial')->after('plan_name');
            $table->text('internal_support_note')->nullable()->after('internal_payment_note');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['manual_payment_status', 'internal_support_note']);
        });
    }
};
