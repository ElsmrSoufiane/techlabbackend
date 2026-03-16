<?php
// database/migrations/2024_01_01_000010_create_product_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->unsigned()->min(1)->max(5);
            $table->text('review')->nullable();
            $table->string('title')->nullable();
            $table->json('images')->nullable();
            $table->boolean('verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            
            // Ensure one review per product per customer
            $table->unique(['product_id', 'customer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_reviews');
    }
};
