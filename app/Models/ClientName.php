<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientName extends Model
{
    protected $table = 'client_name';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'uuid', // Changed from gl_no to uuid
        'lastname',
        'firstname',
        'middlename',
        'suffix',
        'relationship',
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

    public function setRelationshipAttribute($value)
    {
        $this->attributes['relationship'] = $value ? strtoupper($value) : null;
    }

    /**
     * Get the patient history that this client is associated with
     */
    public function patientHistory()
    {
        return $this->belongsTo(PatientHistory::class, 'uuid', 'uuid');
    }
}