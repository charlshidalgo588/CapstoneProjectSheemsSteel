<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');            // 'low_stock', 'out_of_stock', 'sale', 'supplier_delivery'
            $table->string('title');
            $table->string('description');
            $table->morphs('notifiable');       // links to Product, Sale, Supplier, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who it's for, null = all admins
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};