<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckClocksTable extends Migration
{
    public function up()
    {
        Schema::create('check_clocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->enum('check_clock_type', ['clock_in', 'clock_out', 'absent', 'sick_leave', 'annual_leave']);
            $table->timestamp('check_clock_time'); // Waktu absensi
            $table->enum('status', ['late', 'on_time'])->nullable();
            $table->enum('Approval', ['Waiting Approval', 'Approve', 'Reject']);
            $table->string('image')->nullable(); // path ke gambar absensi
            $table->decimal('latitude', 10, 7)->nullable(); // lokasi lat
            $table->decimal('longitude', 10, 7)->nullable(); // lokasi long
            $table->text('address')->nullable(); // alamat lokasi

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')->on('employees')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('check_clocks');
    }
}