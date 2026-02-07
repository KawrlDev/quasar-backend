<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSettings extends Model
{
    protected $table = 'website_settings';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = false;
    protected $keyType = 'int';
    protected $fillable = [
        'eligibility_cooldown',
    ];

}
