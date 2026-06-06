<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'position',
        'overall_experience',
        'top_skills',
        'moderate_skills',
        'cover_letter',
        'status',
        'risk_score',
        'heuristic_flags',
        'review_note',
        'reviewed_at',
    ];

    protected $casts = [
        'top_skills'      => 'array',
        'moderate_skills' => 'array',
        'heuristic_flags' => 'array',
        'reviewed_at'     => 'datetime',
    ];
}
