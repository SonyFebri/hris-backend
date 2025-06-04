<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id'); // foreign key ke tabel users

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('gender', ['M', 'F']); // 'M' = Male, 'F' = Female
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