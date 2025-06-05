<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLetterFormatsTable extends Migration
{
    public function up()
    {
        Schema::create('letter_formats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('letter_name', 100);
            $table->string('status', 100);
            $table->text('path_content');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('letter_formats');
    }
}