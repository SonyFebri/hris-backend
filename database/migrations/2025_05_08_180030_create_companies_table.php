<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 255);
            $table->string('company_code', 255);
            $table->string('bank_name', 255);
            $table->string('bank_number', 255);
            $table->unsignedInteger('subscription_days')->default(14); // Waktu langganan dalam hari
            $table->unsignedInteger('max_employee_count')->default(20); // Maksimal jumlah karyawan
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
}