<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'performed_by', // Changed from gl_no to uuid
        'action',
        'target',
        'changes',
    ];

    public function setPerformedByAttribute($value)
    {
        $this->attributes['performed_by'] = strtoupper($value);
    }

    public function setActionAttribute($value)
    {
        $this->attributes['action'] = strtoupper($value);
    }

    public function setTargetAttribute($value)
    {
        $this->attributes['target'] = $value ? strtoupper($value) : null;
    }
}
