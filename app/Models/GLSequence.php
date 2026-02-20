<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GLSequence extends Model
{
    protected $table = 'gl_sequences';
    
    protected $fillable = [
        'year',
        'current_gl_no',
    ];

    protected $casts = [
        'year' => 'integer',
        'current_gl_no' => 'integer',
    ];

    /**
     * Get the next GL number for the current year (resets yearly)
     */
    public static function getNextGlNo(): int
    {
        $currentYear = now()->year;
        
        return DB::transaction(function () use ($currentYear) {
            $sequence = self::lockForUpdate()
                ->firstOrCreate(
                    ['year' => $currentYear],
                    ['current_gl_no' => 0]
                );
            
            $sequence->increment('current_gl_no');
            
            return $sequence->current_gl_no;
        });
    }

    /**
     * Get the next overall UUID number (never resets)
     */
    public static function getNextUuidNo(): int
    {
        return DB::transaction(function () {
            // Use year 0 as a special marker for the global counter
            $globalSequence = self::lockForUpdate()
                ->firstOrCreate(
                    ['year' => 0],
                    ['current_gl_no' => 0]
                );
            
            $globalSequence->increment('current_gl_no');
            
            return $globalSequence->current_gl_no;
        });
    }

    /**
     * Generate UUID in format: MAMS-YYYY-MM-DD-NNNN
     */
    public static function generateUuid(int $uuidNo): string
    {
        $date = now();
        return sprintf(
            'MAMS-%04d-%02d-%02d-%04d',
            $date->year,
            $date->month,
            $date->day,
            $uuidNo  // This is the global counter that never resets
        );
    }
}