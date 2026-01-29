<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientHistory extends Model
{
    protected $table = 'patient_history';
    protected $primaryKey = 'gl_no';
    public $incrementing = true;
    
    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'patient_id',
        'category',
        'partner',
        'hospital_bill',
        'issued_amount',
        'issued_by',
        'date_issued',
    ];
    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = strtoupper($value);
    }
    public function setIssuedByAttribute($value)
    {
        $this->attributes['issued_by'] = strtoupper($value);
    }
    public function setPartnerAttribute($value)
    {
        $this->attributes['partner'] = strtoupper($value);
    }
}
