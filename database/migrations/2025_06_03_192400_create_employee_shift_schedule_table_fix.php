<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('employee_shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ck_settings_id'); // foreign key ke check_clock_settings
            $table->date('work_date');
            $table->enum('shift_count', ['1', '2', '3'])->default('1');
            $table->unsignedTinyInteger('shift_number');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')->on('employees')
                ->onDelete('cascade');

            $table->foreign('ck_settings_id')
                ->references('id')->on('check_clock_settings')
                ->onDelete('cascade');

            $table->unique(['user_id', 'work_date']); // satu shift per hari per user
            // Menggunakan enum string:


        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_shift_schedules');
    }
};