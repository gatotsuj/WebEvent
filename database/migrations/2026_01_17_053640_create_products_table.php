<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->dateTime('event_date');
            $table->string('event_location');
            $table->time('event_time');
            $table->json('event_details')->nullable();
            $table->enum('status', ['draft', 'published','sold_out','cancelled'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
