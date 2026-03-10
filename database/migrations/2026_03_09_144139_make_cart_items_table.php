<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_cart_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->json('attributes')->nullable();
            $table->string('attributes_hash', 32)->nullable();
            $table->timestamps();
            
            $table->foreign('cart_id')
                  ->references('id')
                  ->on('carts')
                  ->onDelete('cascade');
                  
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
            
            $table->index(['cart_id', 'product_id', 'attributes_hash']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
};