<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_sectors', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('sector_id');

            $table->foreign('patient_id')
                ->references('patient_id')
                ->on('patient_list')
                ->onDelete('cascade');

            $table->foreign('sector_id')
                ->references('id')
                ->on('sectors')
                ->onDelete('cascade');

            $table->unique(['sector_id', 'patient_id']);

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('user_sectors');
    }
};
