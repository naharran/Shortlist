<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'type',
        'related_keywords',
    ];

    protected $casts = [
        'related_keywords' => 'array',
    ];
}
