<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Auth0ApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_protected_routes_reject_requests_without_token(): void
    {
        $response = $this->getJson('/api/applications?status=pending');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_protected_routes_reject_structurally_valid_but_invalid_jwt(): void
    {
        $response = $this->getJson('/api/applications?status=pending', [
            'Authorization' => 'Bearer ' . $this->fakeJwt(),
        ]);

        $response->assertUnauthorized();
        $this->assertStringStartsWith('Invalid token:', $response->json('message'));
    }

    public function test_protected_routes_accept_valid_auth0_token(): void
    {
        $email = env('TEST_REVIEWER_EMAIL');
        $password = env('TEST_REVIEWER_PASSWORD');

        if (! $email || ! $password) {
            $this->markTestSkipped('Set TEST_REVIEWER_EMAIL and TEST_REVIEWER_PASSWORD in .env.backend');
        }

        $token = $this->fetchAuth0AccessToken($email, $password);

        $response = $this->getJson('/api/applications?status=pending', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertOk();
    }

    private function fetchAuth0AccessToken(string $email, string $password): string
    {
        $domain = config('auth0.domain');

        $response = Http::asForm()->post("https://{$domain}/oauth/token", [
            'grant_type'    => 'http://auth0.com/oauth/grant-type/password-realm',
            'username'      => $email,
            'password'      => $password,
            'client_id'     => config('auth0.client_id'),
            'client_secret' => config('auth0.client_secret'),
            'audience'      => config('auth0.audience'),
            'realm'         => 'Username-Password-Authentication',
        ]);

        if ($response->status() === 403 || $response->json('error') === 'unauthorized_client') {
            $this->markTestSkipped(
                'Enable the Password grant type on your Auth0 application (Applications → Settings → Advanced → Grant Types).'
            );
        }

        $response->throw();
        $token = $response->json('access_token');

        $this->assertNotEmpty($token, 'Auth0 did not return an access token.');

        return $token;
    }

    private function fakeJwt(): string
    {
        $header = $this->base64UrlEncode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'fake']);
        $payload = $this->base64UrlEncode([
            'iss' => 'https://fake-tenant.us.auth0.com/',
            'aud' => 'https://shortlist.api',
            'exp' => 1999999999,
            'sub' => 'fake-user',
        ]);

        return "{$header}.{$payload}.invalidsignature";
    }

    private function base64UrlEncode(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }
}
