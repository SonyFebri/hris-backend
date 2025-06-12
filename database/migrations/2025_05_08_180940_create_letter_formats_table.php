<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('letter_name', 100);
            $table->enum('status', ['Pending', 'Approved', 'Rejected']);
            $table->text('path_content');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('letters');
    }
};