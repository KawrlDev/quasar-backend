<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientHistory extends Model
{
    protected $table = 'patient_history';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'gl_no',
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

    protected static function booted()
    {
        static::creating(function ($patientHistory) {
            if (!$patientHistory->uuid) {
                // Get yearly GL number (resets each year)
                $glNo = \App\Models\GlSequence::getNextGlNo();
                
                // Get global UUID number (never resets)
                $uuidNo = \App\Models\GlSequence::getNextUuidNo();
                
                $patientHistory->gl_no = $glNo;
                $patientHistory->uuid = \App\Models\GlSequence::generateUuid($uuidNo);
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(PatientList::class, 'patient_id', 'patient_id');
    }
}