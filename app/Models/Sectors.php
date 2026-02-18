<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sectors extends Model
{
    protected $table = 'sectors';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'sector',
        'is_active',
    ];
    public function setSectorAttribute($value)
    {
        $this->attributes['sector'] = strtoupper($value);
    }
}