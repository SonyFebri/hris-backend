<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckClockSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('check_clock_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->enum('shift_count', ['1', '2', '3'])->default('1');
            $table->unsignedTinyInteger('shift_number');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('check_clock_settings');
    }
}