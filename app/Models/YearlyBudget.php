<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearlyBudget extends Model
{
    protected $table = 'yearly_budget';
    protected $primaryKey = 'year';
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'year',
        'medicine_budget',
        'laboratory_budget',
        'hospital_budget'
    ];
}
