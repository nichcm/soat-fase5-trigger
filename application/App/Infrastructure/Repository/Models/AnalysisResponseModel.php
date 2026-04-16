<?php

namespace App\Infrastructure\Repository\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisResponseModel extends Model
{
    protected $table = 'analysis_responses';

    protected $fillable = [
        'protocol_id',
        'status',
        'content',
        'received_at',
    ];

    protected $casts = [
        'content'     => 'array',
        'received_at' => 'datetime',
    ];

    public $timestamps = false;
}
