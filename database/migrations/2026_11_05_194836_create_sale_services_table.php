<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_services', function (Blueprint $table) {
            $table->id('SaleServiceID');
            $table->unsignedBigInteger('SaleID')->index();
            $table->string('Description', 255);
            $table->decimal('Fee', 12, 2);                       // what the customer paid
            $table->string('WorkerName', 100)->nullable();
            $table->string('WorkerShareType', 10)->nullable();   // 'percent' | 'amount'
            $table->decimal('WorkerShareValue', 12, 2)->default(0);
            $table->decimal('WorkerAmount', 12, 2)->default(0);  // pass-through to the worker, NOT shop revenue
            $table->decimal('OwnerAmount', 12, 2)->default(0);   // the shop's share (counts as sales + profit)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_services');
    }
};