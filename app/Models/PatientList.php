<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientList extends Model
{
    protected $table = 'patient_list';
    protected $primaryKey = 'patient_id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [
        'lastname',
        'firstname',
        'middlename',
        'suffix',
        'birthdate',
        'sex',
        'preference',
        'province',
        'city',
        'barangay',
        'house_address',
        'phone_number',
    ];
    public function setLastnameAttribute($value)
    {
        $this->attributes['lastname'] = strtoupper($value);
    }

    public function setFirstnameAttribute($value)
    {
        $this->attributes['firstname'] = strtoupper($value);
    }

    public function setMiddlenameAttribute($value)
    {
        $this->attributes['middlename'] = $value ? strtoupper($value) : null;
    }

    public function setSuffixAttribute($value)
    {
        $this->attributes['suffix'] = $value ? strtoupper($value) : null;
    }
    public function setHouseAddressAttribute($value)
    {
        $this->attributes['house_address'] = strtoupper($value);
    }
    public function setProvinceAttribute($value)
    {
        $this->attributes['province'] = strtoupper($value);
    }
    public function setCityAttribute($value)
    {
        $this->attributes['city'] = strtoupper($value);
    }
    public function setSexAttribute($value)
    {
        $this->attributes['sex'] = strtoupper($value);
    }
    public function setPreferenceAttribute($value)
    {
        $this->attributes['preference'] = strtoupper($value);
    }
    public function histories()
    {
        return $this->hasMany(PatientHistory::class, 'patient_id', 'patient_id');
    }

    public function details()
    {
        return $this->hasOne(PatientHistory::class, 'patient_id', 'patient_id');
    }
    public function client()
    {
        return $this->hasManyThrough(
            ClientName::class,
            PatientHistory::class,
            'patient_id',
            'uuid',
            'patient_id',
            'uuid'
        );
    }
}
