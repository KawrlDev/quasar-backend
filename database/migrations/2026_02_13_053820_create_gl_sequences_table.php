<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_sequences', function (Blueprint $table) {
            $table->id();
            $table->year('year')->unique();
            $table->unsignedInteger('current_gl_no')->default(0);
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('year');
        });
        
        // Initialize the global counter (year 0) for UUID
        DB::table('gl_sequences')->insert([
            'year' => 0,
            'current_gl_no' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_sequences');
    }
};