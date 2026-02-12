<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preferences extends Model
{
    protected $table = 'preferences';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'preference',
    ];
    public function setPreferenceAttribute($value)
    {
        $this->attributes['preference'] = strtoupper($value);
    }
}