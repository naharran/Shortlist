<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Skill;
use App\Services\HeuristicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeuristicServiceTest extends TestCase
{
    use RefreshDatabase;

    private HeuristicService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HeuristicService();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeApplication(array $overrides = []): Application
    {
        return new Application(array_merge([
            'name'               => 'Test Candidate',
            'email'              => 'test@example.com',
            'phone_number'       => '0501234567',
            'position'           => 'Full-stack Developer',
            'overall_experience' => 3,
            'top_skills'         => ['React', 'Node.js'],
            'moderate_skills'    => [],
            'cover_letter'       => 'I have spent three years building production React applications with component-based architecture and state management using Redux. On the backend I develop REST APIs with Node.js and Express, applying clean code principles and thorough testing. I am comfortable working across the full stack and collaborating in agile teams, and I have hands-on experience with PostgreSQL for data modeling and Docker for containerization and deployment pipelines.',
        ], $overrides));
    }

    private function seedSpecificSkill(string $name, array $keywords = []): Skill
    {
        return Skill::create(['name' => $name, 'type' => 'specific', 'related_keywords' => $keywords]);
    }

    private function seedBroadSkill(string $name): Skill
    {
        return Skill::create(['name' => $name, 'type' => 'broad', 'related_keywords' => []]);
    }

    private function flagKeys(array $flags): array
    {
        return array_column($flags, 'key');
    }

    // ─── Flag: no_top_skills ──────────────────────────────────────────────────

    public function test_no_top_skills_raises_flag_and_penalty(): void
    {
        $app    = $this->makeApplication(['top_skills' => []]);
        $result = $this->service->analyze($app);

        $this->assertContains('no_top_skills', $this->flagKeys($result['heuristic_flags']));
        $this->assertGreaterThanOrEqual(25, $result['risk_score']);
    }

    // ─── Flag: over_claiming_top_skills (experience rule) ─────────────────────

    public function test_top_skills_over_experience_cap_raises_flag(): void
    {
        // 1yr dev cap = 3. Claiming 5 top skills → 2 over cap → +20 penalty
        $app    = $this->makeApplication([
            'overall_experience' => 1,
            'top_skills'         => ['React', 'Vue', 'Node.js', 'Laravel', 'TypeScript'],
        ]);
        $result = $this->service->analyze($app);

        $this->assertContains('over_claiming_top_skills', $this->flagKeys($result['heuristic_flags']));
        $this->assertGreaterThanOrEqual(20, $result['risk_score']);
    }

    // ─── Flag: over_claiming_top_skills (optimal rule) ────────────────────────

    public function test_top_skills_over_optimal_cap_raises_mild_flag(): void
    {
        // 8yr dev cap = 8. Claiming 7 skills is within cap but above optimal(6) → mild penalty
        $app    = $this->makeApplication([
            'overall_experience' => 8,
            'top_skills'         => ['React', 'Vue', 'Node.js', 'Laravel', 'TypeScript', 'Docker', 'PostgreSQL'],
        ]);
        $result = $this->service->analyze($app);

        $this->assertContains('over_claiming_top_skills', $this->flagKeys($result['heuristic_flags']));
        $this->assertGreaterThan(0, $result['risk_score']);
        $this->assertLessThan(20, $result['risk_score']); // mild, not heavy
    }

    // ─── Flag: over_claiming_broad_skills ─────────────────────────────────────

    public function test_too_many_broad_skills_raises_flag(): void
    {
        $this->seedBroadSkill('Full-stack Development');
        $this->seedBroadSkill('Front-end Development');
        $this->seedBroadSkill('Back-end Development');
        $this->seedBroadSkill('DevOps');
        $this->seedBroadSkill('System Design');

        // 1yr dev: experienceCap = 2. Claiming 5 broad skills → 3 over cap → +30
        $app    = $this->makeApplication([
            'overall_experience' => 1,
            'top_skills'         => ['Full-stack Development', 'Front-end Development', 'Back-end Development'],
            'moderate_skills'    => ['DevOps', 'System Design'],
        ]);
        $result = $this->service->analyze($app);

        $this->assertContains('over_claiming_broad_skills', $this->flagKeys($result['heuristic_flags']));
    }

    // ─── Flag: poor_skill_explanation_coverage ────────────────────────────────

    public function test_poor_cover_letter_coverage_raises_flag(): void
    {
        $this->seedSpecificSkill('React', ['react', 'jsx', 'component-based']);
        $this->seedSpecificSkill('Vue', ['vue', 'pinia', 'nuxt']);
        $this->seedSpecificSkill('Node.js', ['node', 'express', 'api server']);
        $this->seedSpecificSkill('Laravel', ['laravel', 'php', 'eloquent']);

        $app = $this->makeApplication([
            'top_skills'   => ['React', 'Vue', 'Node.js', 'Laravel'],
            'cover_letter' => 'I am a passionate developer who loves to learn new things and work in teams.',
        ]);
        $result = $this->service->analyze($app);

        $this->assertContains('poor_skill_explanation_coverage', $this->flagKeys($result['heuristic_flags']));
    }

    public function test_good_cover_letter_coverage_no_flag(): void
    {
        $this->seedSpecificSkill('React', ['react', 'component-based', 'state management']);
        $this->seedSpecificSkill('Node.js', ['node', 'express', 'rest api']);

        $app = $this->makeApplication([
            'top_skills'   => ['React', 'Node.js'],
            'cover_letter' => 'I have 3 years building React applications with component-based architecture and state management. On the backend I use Node.js with Express to build REST APIs.',
        ]);
        $result = $this->service->analyze($app);

        $this->assertNotContains('poor_skill_explanation_coverage', $this->flagKeys($result['heuristic_flags']));
    }

    // ─── Flag: very_short_cover_letter ────────────────────────────────────────

    public function test_very_short_cover_letter_raises_flag(): void
    {
        $app    = $this->makeApplication(['cover_letter' => 'I am a developer. Hire me.']);
        $result = $this->service->analyze($app);

        $this->assertContains('very_short_cover_letter', $this->flagKeys($result['heuristic_flags']));
        $this->assertGreaterThanOrEqual(15, $result['risk_score']);
    }

    public function test_sufficient_cover_letter_no_flag(): void
    {
        $app    = $this->makeApplication(); // default cover letter is long enough
        $result = $this->service->analyze($app);

        $this->assertNotContains('very_short_cover_letter', $this->flagKeys($result['heuristic_flags']));
    }

    // ─── Flag: suspicious_experience ─────────────────────────────────────────

    public function test_unrealistically_high_experience_raises_flag(): void
    {
        $app    = $this->makeApplication(['overall_experience' => 50]);
        $result = $this->service->analyze($app);

        $this->assertContains('suspicious_experience', $this->flagKeys($result['heuristic_flags']));
    }

    public function test_zero_experience_with_top_skills_raises_flag(): void
    {
        $app    = $this->makeApplication(['overall_experience' => 0, 'top_skills' => ['React', 'Node.js']]);
        $result = $this->service->analyze($app);

        $this->assertContains('suspicious_experience', $this->flagKeys($result['heuristic_flags']));
    }

    // ─── General: clean candidate ─────────────────────────────────────────────

    public function test_clean_candidate_has_low_score_and_no_flags(): void
    {
        $this->seedSpecificSkill('React', ['react', 'component-based', 'state management']);
        $this->seedSpecificSkill('Node.js', ['node', 'rest api', 'express']);

        $app = $this->makeApplication([
            'overall_experience' => 4,
            'top_skills'         => ['React', 'Node.js'],
            'moderate_skills'    => [],
            'cover_letter'       => 'I have spent four years building React applications using component-based architecture and state management with Redux. On the backend I design REST APIs with Node.js and Express, focusing on performance and maintainability. I write unit and integration tests and I am comfortable collaborating in agile teams with code reviews and CI/CD pipelines.',
        ]);
        $result = $this->service->analyze($app);

        $this->assertEmpty($result['heuristic_flags']);
        $this->assertEquals(0, $result['risk_score']);
    }

    // ─── General: risky candidate ─────────────────────────────────────────────

    public function test_risky_candidate_accumulates_high_score(): void
    {
        $this->seedBroadSkill('Full-stack Development');
        $this->seedBroadSkill('DevOps');
        $this->seedBroadSkill('System Design');
        $this->seedSpecificSkill('React', ['react', 'jsx']);
        $this->seedSpecificSkill('Vue', ['vue', 'pinia']);
        $this->seedSpecificSkill('Angular', ['angular']);
        $this->seedSpecificSkill('Node.js', ['node', 'express']);
        $this->seedSpecificSkill('Laravel', ['laravel', 'php']);

        // 1yr dev claiming 8 top skills + short generic cover letter
        $app = $this->makeApplication([
            'overall_experience' => 1,
            'top_skills'         => ['React', 'Vue', 'Angular', 'Node.js', 'Laravel', 'Full-stack Development', 'DevOps', 'System Design'],
            'moderate_skills'    => [],
            'cover_letter'       => 'I am passionate and a quick learner.',
        ]);
        $result = $this->service->analyze($app);

        $this->assertGreaterThanOrEqual(50, $result['risk_score']);
        $this->assertNotEmpty($result['heuristic_flags']);
    }
}
