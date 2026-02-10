<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplementary_bonus', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->date('date_added')->nullable();
            $table->decimal('medicine_supplementary_bonus', 15, 2)->nullable();
            $table->decimal('laboratory_supplementary_bonus', 15, 2)->nullable();
            $table->decimal('hospital_supplementary_bonus', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('year')->references('year')->on('yearly_budget')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplementary_bonus');
    }
};

