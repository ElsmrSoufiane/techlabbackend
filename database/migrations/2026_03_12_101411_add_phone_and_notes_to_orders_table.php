<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  // In the migration file:
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('phone')->nullable()->after('shipping_address');
        $table->text('notes')->nullable()->after('phone');
    });
}



    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn(['phone', 'notes']);
    });
}
};
