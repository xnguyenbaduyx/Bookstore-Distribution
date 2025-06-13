<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('branch_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('book_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('book_id')->references('id')->on('books');
            $table->unique(['branch_id', 'book_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_inventories');
    }
};