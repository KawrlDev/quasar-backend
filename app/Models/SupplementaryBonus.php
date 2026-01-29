<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplementaryBonus extends Model
{
    protected $table = 'supplementary_bonus';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'year',
        'date_added',
        'medicine_supplementary_bonus',
        'laboratory_supplementary_bonus',
        'hospital_supplementary_bonus',
    ];
}
