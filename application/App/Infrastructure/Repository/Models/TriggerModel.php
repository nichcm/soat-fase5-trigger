<?php

namespace App\Infrastructure\Repository\Models;

use Illuminate\Database\Eloquent\Model;

class TriggerModel extends Model
{
    protected $table = 'triggers';

    protected $fillable = [
        'protocol_uuid',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;
}
