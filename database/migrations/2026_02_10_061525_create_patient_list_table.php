<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_list', function (Blueprint $table) {
            $table->id('patient_id');
            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('suffix')->nullable();
            $table->date('birthdate');
            $table->string('sex');
            $table->string('preference')->nullable();
            $table->string('province');
            $table->string('city');
            $table->string('barangay');
            $table->string('house_address');
            $table->string('phone_number', 11);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_list');
    }
};
