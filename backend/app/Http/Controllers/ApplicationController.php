<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Skill;
use App\Services\HeuristicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status'         => ['sometimes', Rule::in(['pending', 'shortlisted', 'rejected'])],
            'search'         => ['sometimes', 'string', 'max:200'],
            'min_experience' => ['sometimes', 'integer', 'min:0'],
            'max_experience' => ['sometimes', 'integer', 'min:0'],
            'min_risk'       => ['sometimes', 'integer', 'min:0', 'max:100'],
            'max_risk'       => ['sometimes', 'integer', 'min:0', 'max:100'],
            'skill_ids'      => ['sometimes', 'array'],
            'skill_ids.*'    => ['integer'],
        ]);

        $query = Application::query()
            ->select([
                'id',
                'name',
                'email',
                'position',
                'overall_experience',
                'status',
                'risk_score',
                'heuristic_flags',
                'created_at',
            ])
            ->orderByDesc('created_at');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $term = $validated['search'];
            $matchingIds = \DB::table('applications_fts')
                ->whereRaw('applications_fts MATCH ?', ["\"{$term}\"*"])
                ->pluck('rowid');
            $query->whereIn('id', $matchingIds);
        }

        if (isset($validated['min_experience'])) {
            $query->where('overall_experience', '>=', $validated['min_experience']);
        }

        if (isset($validated['max_experience'])) {
            $query->where('overall_experience', '<=', $validated['max_experience']);
        }

        if (isset($validated['min_risk'])) {
            $query->where('risk_score', '>=', $validated['min_risk']);
        }

        if (isset($validated['max_risk'])) {
            $query->where('risk_score', '<=', $validated['max_risk']);
        }

        if (! empty($validated['skill_ids'])) {
            foreach ($validated['skill_ids'] as $skillId) {
                $query->where(function ($q) use ($skillId) {
                    $q->whereJsonContains('top_skills', $skillId)
                      ->orWhereJsonContains('moderate_skills', $skillId);
                });
            }
        }

        return response()->json($query->get());
    }

    public function show(Application $application): JsonResponse
    {
        return response()->json($application);
    }

    public function review(Request $request, Application $application): JsonResponse
    {
        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Application already reviewed.'], 422);
        }

        $validated = $request->validate([
            'status'      => ['required', Rule::in(['shortlisted', 'rejected'])],
            'review_note' => ['nullable', 'string'],
        ]);

        $application->update([
            'status'      => $validated['status'],
            'review_note' => $validated['review_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        return response()->json($application);
    }

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

        $rateLimitKey = 'application-submit:'.$request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json(['message' => 'Too Many Attempts.'], 429);
        }

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

        RateLimiter::hit($rateLimitKey, 60);

        return response()->json($application, 201);
    }
}
