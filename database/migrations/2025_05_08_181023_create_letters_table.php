<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLettersTable extends Migration
{
    public function up()
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('letter_format_id');
            $table->uuid('user_id');
            $table->string('name', 100);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('letter_format_id')->references('id')->on('letter_formats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('letters');
    }
}