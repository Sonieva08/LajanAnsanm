<?php

namespace Tests\Feature;

use App\Http\Middleware\SecureResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersAndSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_added_to_web_responses(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_production_https_responses_get_hsts_and_a_nonce_based_csp(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['app.force_https' => false]);

        $response = $this->withHeader('X-Forwarded-Proto', 'https')->get('/');

        $response->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');

        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertIsString($policy);
        $this->assertStringContainsString("script-src 'self' 'unsafe-eval' 'nonce-", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringNotContainsString('form-action', $policy);
    }

    public function test_production_http_requests_are_redirected_to_https(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['app.force_https' => true]);

        config(['app.url' => 'https://canonical.example.test']);
        $request = Request::create('http://attacker.example.test/dashboard', 'GET');
        $response = (new SecureResponse)->handle($request, fn () => response('should not reach the route'));

        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('https://canonical.example.test/dashboard', $response->headers->get('Location'));
    }

    public function test_authenticated_pages_are_not_cached_and_sessions_expire_after_the_absolute_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        $this->actingAs($user)
            ->withSession(['security.authenticated_at' => now()->subHours(9)->timestamp])
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }
}
