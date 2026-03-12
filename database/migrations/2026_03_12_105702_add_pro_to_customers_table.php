<?php
// database/migrations/2026_03_12_000003_add_pro_to_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('tier')->default('regular')->after('role');
            $table->integer('pro_discount')->nullable()->after('tier');
            $table->string('company_name')->nullable()->after('pro_discount');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tier', 'pro_discount', 'company_name']);
        });
    }
};