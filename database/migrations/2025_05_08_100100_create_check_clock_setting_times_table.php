<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckClockSettingTimesTable extends Migration
{
    public function up()
    {
        Schema::create('check_clock_setting_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ck_settings_id');
            $table->time('clock_in');
            $table->time('clock_out');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ck_settings_id')
                ->references('id')->on('check_clock_settings')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('check_clock_setting_times');
    }
}