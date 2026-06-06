<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Skill;

class HeuristicService
{
    public function analyze(Application $application): array
    {
        $flags = [];
        $score = 0;

        $score += $this->checkOverClaimingTopSkills($application, $flags);
        $score += $this->checkOverClaimingBroadSkills($application, $flags);
        $score += $this->checkSkillExplanationCoverage($application, $flags);
        $score += $this->checkVeryShortCoverLetter($application, $flags);
        $score += $this->checkSuspiciousExperience($application, $flags);

        return [
            'risk_score'      => min(100, $score),
            'heuristic_flags' => $flags,
        ];
    }

    private function checkOverClaimingTopSkills(Application $application, array &$flags): int
    {
        $count      = count($application->top_skills);
        $experience = $application->overall_experience;

        if ($count === 0) {
            $flags[] = ['key' => 'no_top_skills'];
            return 25;
        }

        // 1yr → cap 3, 2yr → cap 4, ..., 6yr+ → cap 8
        $experienceCap = min(8, 2 + $experience);
        $optimalCap    = 6;

        $penalty = 0;

        if ($count > $experienceCap) {
            // Experience rule: already over the cap — penalize linearly, skip optimal rule
            $penalty += ($count - $experienceCap) * 10;
            $flags[]  = [
                'key'   => 'over_claiming_top_skills',
                'count' => $count,
                'cap'   => $experienceCap,
            ];
        } elseif ($count > $optimalCap) {
            // Optimal rule: within experience cap but above the ideal focus of 6
            $penalty += ($count - $optimalCap) * 3;
            $flags[]  = [
                'key'   => 'over_claiming_top_skills',
                'count' => $count,
                'cap'   => $experienceCap,
            ];
        }

        return $penalty;
    }

    private function checkOverClaimingBroadSkills(Application $application, array &$flags): int
    {
        $experience = $application->overall_experience;

        $allClaimedNames = array_merge(
            $application->top_skills ?? [],
            $application->moderate_skills ?? []
        );

        // Count how many of the claimed skills are of type 'broad' in the DB
        $count = Skill::where('type', 'broad')
            ->whereIn('name', $allClaimedNames)
            ->count();

        if ($count === 0) {
            return 0;
        }

        // Start with 2, +1 every 2 years: 1yr→2, 2yr→3, 4yr→4 ...
        $experienceCap = 2 + (int) floor($experience / 2);
        $optimalCap    = 4;

        $penalty = 0;

        if ($count > $experienceCap) {
            $penalty += ($count - $experienceCap) * 10;
            $flags[]  = [
                'key'   => 'over_claiming_broad_skills',
                'count' => $count,
                'cap'   => $experienceCap,
            ];
        } elseif ($count > $optimalCap) {
            $penalty += ($count - $optimalCap) * 3;
            $flags[]  = [
                'key'   => 'over_claiming_broad_skills',
                'count' => $count,
                'cap'   => $experienceCap,
            ];
        }

        return $penalty;
    }

    private function checkSkillExplanationCoverage(Application $application, array &$flags): int
    {
        $topSkillNames      = $application->top_skills ?? [];
        $moderateSkillNames = $application->moderate_skills ?? [];
        $allClaimedNames    = array_merge($topSkillNames, $moderateSkillNames);

        if (empty($allClaimedNames)) {
            return 0;
        }

        $specificSkills = Skill::where('type', 'specific')
            ->whereIn('name', $allClaimedNames)
            ->get();

        if ($specificSkills->isEmpty()) {
            return 0;
        }

        $coverLetter    = strtolower($application->cover_letter);
        $weightedTotal  = 0.0;
        $weightedCovered = 0.0;

        foreach ($specificSkills as $skill) {
            // Top skills matter more — moderate skills carry half the weight
            $weight          = in_array($skill->name, $topSkillNames) ? 1.0 : 0.5;
            $weightedTotal  += $weight;

            $terms = array_merge([strtolower($skill->name)], array_map('strtolower', $skill->related_keywords));

            foreach ($terms as $term) {
                if (str_contains($coverLetter, $term)) {
                    $weightedCovered += $weight;
                    break;
                }
            }
        }

        $coverage = $weightedCovered / $weightedTotal;

        if ($coverage >= 0.4) {
            return 0;
        }

        // Scale penalty: 0% coverage → 30pts, approaching 40% → 0pts
        $penalty = (int) round((0.4 - $coverage) / 0.4 * 30);

        $flags[] = [
            'key'      => 'poor_skill_explanation_coverage',
            'coverage' => round($coverage * 100) . '%',
        ];

        return $penalty;
    }

    private function checkVeryShortCoverLetter(Application $application, array &$flags): int
    {
        $text     = trim($application->cover_letter);
        $wordCount = $text === '' ? 0 : count(preg_split('/\s+/', $text));

        if ($wordCount >= 50) {
            return 0;
        }

        $flags[] = [
            'key'        => 'very_short_cover_letter',
            'word_count' => $wordCount,
        ];

        return 15;
    }

    private function checkSuspiciousExperience(Application $application, array &$flags): int
    {
        $experience = $application->overall_experience;
        $topSkills  = count($application->top_skills ?? []);

        $isUnrealisticallyHigh = $experience > 40;
        $isZeroWithTopSkills   = $experience === 0 && $topSkills > 0;

        if (! $isUnrealisticallyHigh && ! $isZeroWithTopSkills) {
            return 0;
        }

        $flags[] = [
            'key'        => 'suspicious_experience',
            'experience' => $experience,
            'top_skills' => $topSkills,
        ];

        return 20;
    }
}
