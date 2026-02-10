<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_name', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gl_no');
            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('suffix')->nullable();
            $table->string('relationship')->nullable();
            $table->timestamps();

            $table->foreign('gl_no')->references('gl_no')->on('patient_history')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_name');
    }
};
