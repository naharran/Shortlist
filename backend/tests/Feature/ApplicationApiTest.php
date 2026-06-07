<?php

namespace Tests\Feature;

use App\Http\Middleware\Auth0Middleware;
use App\Models\Application;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    private Skill $reactSkill;

    private Skill $nodeSkill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Auth0Middleware::class);

        $this->reactSkill = Skill::create([
            'name'             => 'React',
            'type'             => 'specific',
            'related_keywords' => ['react', 'component-based', 'state management'],
        ]);

        $this->nodeSkill = Skill::create([
            'name'             => 'Node.js',
            'type'             => 'specific',
            'related_keywords' => ['node', 'express', 'rest api'],
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'               => 'Jane Doe',
            'email'              => 'jane@example.com',
            'phone_number'       => '0501234567',
            'position'           => 'Full-stack Developer',
            'overall_experience' => 3,
            'top_skills'         => [$this->reactSkill->id, $this->nodeSkill->id],
            'moderate_skills'    => [],
            'cover_letter'       => 'I have spent three years building production React applications with component-based architecture and state management using Redux. On the backend I develop REST APIs with Node.js and Express, applying clean code principles and thorough testing. I am comfortable working across the full stack and collaborating in agile teams, and I have hands-on experience with PostgreSQL for data modeling and Docker for containerization and deployment pipelines.',
        ], $overrides);
    }

    private function createPendingApplication(array $overrides = []): Application
    {
        $response = $this->postJson('/api/applications', $this->validPayload($overrides));
        $response->assertCreated();

        return Application::findOrFail($response->json('id'));
    }

    // ─── POST /api/applications ───────────────────────────────────────────────

    public function test_store_creates_application_with_heuristic_results(): void
    {
        $response = $this->postJson('/api/applications', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('name', 'Jane Doe')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('top_skills', [$this->reactSkill->id, $this->nodeSkill->id])
            ->assertJsonStructure(['risk_score', 'heuristic_flags']);

        $this->assertDatabaseHas('applications', [
            'email'  => 'jane@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_store_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/applications', $this->validPayload([
            'email' => 'not-an-email',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_rejects_unknown_skill_id(): void
    {
        $response = $this->postJson('/api/applications', $this->validPayload([
            'top_skills' => [9999],
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['top_skills.0']);
    }

    public function test_store_validation_failures_do_not_count_toward_rate_limit(): void
    {
        RateLimiter::clear('application-submit:127.0.0.1');

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/applications', $this->validPayload([
                'cover_letter' => '',
            ]))->assertUnprocessable();
        }

        $this->postJson('/api/applications', $this->validPayload())->assertCreated();
    }

    public function test_store_returns_429_after_five_submissions_per_minute(): void
    {
        RateLimiter::clear('application-submit:127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/applications', $this->validPayload([
                'email' => "applicant{$i}@example.com",
            ]))->assertCreated();
        }

        $this->postJson('/api/applications', $this->validPayload([
            'email' => 'applicant6@example.com',
        ]))->assertTooManyRequests();
    }

    // ─── GET /api/applications ──────────────────────────────────────────────

    public function test_index_filters_by_status(): void
    {
        $pending = $this->createPendingApplication(['email' => 'pending@example.com']);
        $this->createPendingApplication(['email' => 'shortlisted@example.com']);
        $this->createPendingApplication(['email' => 'rejected@example.com']);

        Application::where('email', 'shortlisted@example.com')->update(['status' => 'shortlisted']);
        Application::where('email', 'rejected@example.com')->update(['status' => 'rejected']);

        $response = $this->getJson('/api/applications?status=pending');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $pending->id)
            ->assertJsonPath('0.email', 'pending@example.com');
    }

    public function test_index_returns_summary_fields_only(): void
    {
        $this->createPendingApplication();

        $response = $this->getJson('/api/applications?status=pending');

        $response->assertOk();

        $item = $response->json('0');

        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('risk_score', $item);
        $this->assertArrayNotHasKey('cover_letter', $item);
        $this->assertArrayNotHasKey('top_skills', $item);
    }

    // ─── GET /api/applications/{id} ─────────────────────────────────────────

    public function test_show_returns_full_application(): void
    {
        $application = $this->createPendingApplication();

        $response = $this->getJson("/api/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('id', $application->id)
            ->assertJsonPath('cover_letter', $application->cover_letter)
            ->assertJsonPath('top_skills', [$this->reactSkill->id, $this->nodeSkill->id]);
    }

    public function test_show_returns_404_for_missing_application(): void
    {
        $response = $this->getJson('/api/applications/9999');

        $response->assertNotFound();
    }

    // ─── PATCH /api/applications/{id}/review ────────────────────────────────

    public function test_review_shortlists_application(): void
    {
        $application = $this->createPendingApplication();

        $response = $this->patchJson("/api/applications/{$application->id}/review", [
            'status' => 'shortlisted',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'shortlisted');

        $this->assertDatabaseHas('applications', [
            'id'     => $application->id,
            'status' => 'shortlisted',
        ]);

        $this->assertNotNull($response->json('reviewed_at'));
    }

    public function test_review_rejects_application_with_note(): void
    {
        $application = $this->createPendingApplication();

        $response = $this->patchJson("/api/applications/{$application->id}/review", [
            'status'      => 'rejected',
            'review_note' => 'Insufficient experience.',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('review_note', 'Insufficient experience.');
    }

    public function test_review_fails_when_already_reviewed(): void
    {
        $application = $this->createPendingApplication();

        $this->patchJson("/api/applications/{$application->id}/review", [
            'status' => 'shortlisted',
        ])->assertOk();

        $response = $this->patchJson("/api/applications/{$application->id}/review", [
            'status' => 'rejected',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Application already reviewed.');
    }
}
