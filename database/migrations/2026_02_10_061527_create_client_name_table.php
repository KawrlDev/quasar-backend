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
            $table->string('uuid'); // Changed from gl_no to uuid
            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('suffix')->nullable();
            $table->string('relationship')->nullable();
            $table->timestamps();

            $table->foreign('uuid')->references('uuid')->on('patient_history')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_name');
    }
};