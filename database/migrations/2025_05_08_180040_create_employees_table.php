<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id'); // foreign key ke tabel users
            $table->string('id_employee', 100);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('NIK', 100);
            $table->enum('last_education', ['SD', 'SMP', 'SMA/SMK', 'D2', 'D3', 'D4', 'S1', 'S2']);
            $table->string('place_birth', 100);
            $table->date('date_birth');
            $table->string('role', 100);
            $table->string('branch', 100);
            $table->enum('contract_type', ['permanen', 'percobaan', 'magang', 'kontrak']);
            $table->string('bank', 100);
            $table->string('bank_account_number', 100);
            $table->string('bank_account_name', 100);
            $table->string('position', 100);
            $table->enum('gender', ['Laki-laki', 'Perempuan']);
            $table->enum('SP', ['1', '2', '3']);
            $table->text('address')->nullable();
            $table->enum('shift_count', ['1', '2', '3'])->default('1');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade'); // sesuaikan aturan onDelete
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
}