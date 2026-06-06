<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Skill;
use App\Services\HeuristicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function store(Request $request, HeuristicService $heuristicService): JsonResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'max:255'],
            'phone_number'       => ['required', 'string', 'max:50'],
            'position'           => ['required', 'string', 'max:255'],
            'overall_experience' => ['required', 'integer', 'min:0', 'max:50'],
            'top_skills'         => ['required', 'array'],
            'top_skills.*'       => ['integer', Rule::exists('skills', 'id')],
            'moderate_skills'    => ['array'],
            'moderate_skills.*'  => ['integer', Rule::exists('skills', 'id')],
            'cover_letter'       => ['required', 'string'],
        ]);

        $topSkillIds      = $validated['top_skills'];
        $moderateSkillIds = $validated['moderate_skills'] ?? [];
        $validated['moderate_skills'] = $moderateSkillIds;
        $validated['status']          = 'pending';

        // DB stores skill IDs; HeuristicService works with names
        $skillNames = Skill::whereIn('id', array_merge($topSkillIds, $moderateSkillIds))
            ->pluck('name', 'id');

        $applicationForAnalysis = new Application([
            ...$validated,
            'top_skills'      => array_map(fn (int $id) => $skillNames[$id], $topSkillIds),
            'moderate_skills' => array_map(fn (int $id) => $skillNames[$id], $moderateSkillIds),
        ]);

        $application = new Application($validated);

        $analysis = $heuristicService->analyze($applicationForAnalysis);

        $application->risk_score      = $analysis['risk_score'];
        $application->heuristic_flags = $analysis['heuristic_flags'];
        $application->save();

        return response()->json($application, 201);
    }
}
