<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('import_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->unsignedBigInteger('book_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
            
            $table->foreign('import_id')->references('id')->on('imports')->onDelete('cascade');
            $table->foreign('book_id')->references('id')->on('books');
        });
    }

    public function down()
    {
        Schema::dropIfExists('import_details');
    }
};