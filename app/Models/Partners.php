<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partners extends Model
{
    protected $table = 'partners';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'category',
        'partner',
    ];
    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = strtoupper($value);
    }
    public function setPartnerAttribute($value)
    {
        $this->attributes['partner'] = strtoupper($value);
    }
}
