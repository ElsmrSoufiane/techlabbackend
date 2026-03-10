<?php
// database/migrations/2026_03_01_000003_create_customer_coupon_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_coupon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->boolean('is_used')->default(false);
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
            
            $table->unique(['customer_id', 'coupon_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_coupon');
    }
};