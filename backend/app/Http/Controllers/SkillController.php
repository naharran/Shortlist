<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    public function index(): JsonResponse
    {
        $skills = Skill::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return response()->json($skills);
    }
}
