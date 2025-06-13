<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_request_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_request_id');
            $table->unsignedBigInteger('book_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
            
            $table->foreign('order_request_id')->references('id')->on('order_requests')->onDelete('cascade');
            $table->foreign('book_id')->references('id')->on('books');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_request_details');
    }
};