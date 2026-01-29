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
    }    public function setHouseAddressAttribute($value)
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
    public function details()
    {
        return $this->hasOne(PatientHistory::class, 'gl_no', 'gl_no');
    }
    public function client()
    {
        return $this->hasOne(ClientName::class, 'gl_no', 'gl_no');
    }
}
