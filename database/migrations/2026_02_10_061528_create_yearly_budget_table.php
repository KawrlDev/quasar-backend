<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yearly_budget', function (Blueprint $table) {
            $table->year('year')->primary();
            $table->decimal('medicine_budget', 15, 2)->nullable();
            $table->decimal('laboratory_budget', 15, 2)->nullable();
            $table->decimal('hospital_budget', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yearly_budget');
    }
};

