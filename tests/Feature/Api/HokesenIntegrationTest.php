<?php

namespace Tests\Feature\Api;

use App\Enums\SessionStatus;
use App\Models\JournalEntry;
use App\Models\JournalExercise;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class HokesenIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-02-22 16:00:00', 'UTC'));
        Cache::flush();

        config()->set('integrations.hokesen.enabled', true);
        config()->set('integrations.hokesen.issuer', 'hokesen.dev');
        config()->set('integrations.hokesen.audience', 'dailycalisthenic');
        config()->set('integrations.hokesen.shared_secret', 'test-shared-secret');
        config()->set('integrations.hokesen.previous_shared_secret', null);
        config()->set('integrations.hokesen.clock_skew_seconds', 60);
        config()->set('integrations.hokesen.max_ttl_seconds', 300);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_quick_stats_requires_bearer_assertion(): void
    {
        $response = $this->getJson('/api/integrations/hokesen/v1/quick-stats');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'missing_token');
    }

    public function test_quick_stats_accepts_assertion_from_fallback_header(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->getJson('/api/integrations/hokesen/v1/quick-stats', [
            'X-Hokesen-Assertion' => $this->makeAssertion('user@example.com'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('email', 'user@example.com');
    }

    public function test_quick_stats_rejects_invalid_signature(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);

        $token = $this->makeAssertion('user@example.com');

        config()->set('integrations.hokesen.shared_secret', 'different-secret');

        $response = $this->getJson('/api/integrations/hokesen/v1/quick-stats', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'invalid_signature');
    }

    public function test_quick_stats_requires_matching_verified_local_email(): void
    {
        User::factory()->unverified()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->getJson('/api/integrations/hokesen/v1/quick-stats', [
            'Authorization' => 'Bearer '.$this->makeAssertion('user@example.com'),
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'user_not_linked_or_unverified');
    }

    public function test_quick_stats_returns_streak_and_last_thirty_day_activity_summary(): void
    {
        $user = User::factory()->create([
            'email' => 'CaseSensitive@example.com',
            'email_verified_at' => now(),
            'timezone' => 'America/Los_Angeles',
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed->value,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 900,
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed->value,
            'completed_at' => $user->now()->subDay()->timezone('UTC'),
            'total_duration_seconds' => 1500,
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed->value,
            'completed_at' => $user->now()->subDays(31)->timezone('UTC'),
            'total_duration_seconds' => 999,
        ]);

        $journalEntryInRangeWithDuration = JournalEntry::factory()->create([
            'user_id' => $user->id,
            'entry_date' => $user->now()->subDays(5)->toDateString(),
            'notes' => 'In-range entry with duration',
        ]);

        JournalExercise::factory()->create([
            'journal_entry_id' => $journalEntryInRangeWithDuration->id,
            'duration_minutes' => 25,
        ]);

        JournalEntry::factory()->create([
            'user_id' => $user->id,
            'entry_date' => $user->now()->subDays(10)->toDateString(),
            'notes' => 'In-range entry without duration',
        ]);

        $journalEntryOutOfRange = JournalEntry::factory()->create([
            'user_id' => $user->id,
            'entry_date' => $user->now()->subDays(31)->toDateString(),
            'notes' => 'Out-of-range entry',
        ]);

        JournalExercise::factory()->create([
            'journal_entry_id' => $journalEntryOutOfRange->id,
            'duration_minutes' => 60,
        ]);

        $response = $this->getJson('/api/integrations/hokesen/v1/quick-stats', [
            'Authorization' => 'Bearer '.$this->makeAssertion('casesensitive@example.com'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('streak_days', 2);
        $response->assertJsonPath('practice_duration_seconds_last_30_days', 3900);
        $response->assertJsonPath('sessions_last_30_days', 2);
        $response->assertJsonPath('journal_entries_last_30_days', 2);
        $response->assertJsonPath('email', 'casesensitive@example.com');
        $response->assertJsonStructure(['as_of', 'timezone']);
    }

    public function test_journal_line_creates_and_then_appends_to_daily_entry(): void
    {
        $user = User::factory()->create([
            'email' => 'journal@example.com',
            'email_verified_at' => now(),
            'timezone' => 'America/Los_Angeles',
        ]);

        $createResponse = $this->postJson('/api/integrations/hokesen/v1/journal-line', [
            'text' => 'First one-line note',
        ], [
            'Authorization' => 'Bearer '.$this->makeAssertion('journal@example.com'),
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonPath('created', true);
        $createResponse->assertJsonPath('appended', false);

        $appendResponse = $this->postJson('/api/integrations/hokesen/v1/journal-line', [
            'text' => 'Second one-line note',
        ], [
            'Authorization' => 'Bearer '.$this->makeAssertion('journal@example.com'),
        ]);

        $appendResponse->assertOk();
        $appendResponse->assertJsonPath('created', false);
        $appendResponse->assertJsonPath('appended', true);

        $entry = JournalEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', $user->now()->toDateString())
            ->firstOrFail();

        $this->assertSame(
            'First one-line note'."\n".'Second one-line note',
            $entry->notes
        );
    }

    public function test_journal_line_accepts_assertion_from_fallback_header(): void
    {
        User::factory()->create([
            'email' => 'header@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/integrations/hokesen/v1/journal-line', [
            'text' => 'Header auth works',
        ], [
            'X-Hokesen-Assertion' => $this->makeAssertion('header@example.com'),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('created', true);
    }

    public function test_journal_line_idempotency_key_prevents_duplicate_writes(): void
    {
        $user = User::factory()->create([
            'email' => 'idempotent@example.com',
            'email_verified_at' => now(),
        ]);

        $headers = ['Idempotency-Key' => 'same-request'];

        $firstResponse = $this->postJson('/api/integrations/hokesen/v1/journal-line', [
            'text' => 'Log this once',
        ], $headers + [
            'Authorization' => 'Bearer '.$this->makeAssertion('idempotent@example.com'),
        ]);

        $firstResponse->assertStatus(201);
        $firstResponse->assertJsonPath('created', true);

        $secondResponse = $this->postJson('/api/integrations/hokesen/v1/journal-line', [
            'text' => 'Log this once',
        ], $headers + [
            'Authorization' => 'Bearer '.$this->makeAssertion('idempotent@example.com'),
        ]);

        $secondResponse->assertOk();
        $secondResponse->assertJsonPath('idempotent_replay', true);

        $entry = JournalEntry::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Log this once', $entry->notes);
    }

    public function test_assertion_replay_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'replay@example.com',
            'email_verified_at' => now(),
        ]);

        $token = $this->makeAssertion('replay@example.com', ['jti' => 'same-jti']);

        $this->getJson('/api/integrations/hokesen/v1/quick-stats', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $secondResponse = $this->getJson('/api/integrations/hokesen/v1/quick-stats', [
            'Authorization' => "Bearer {$token}",
        ]);

        $secondResponse->assertStatus(401);
        $secondResponse->assertJsonPath('error.code', 'replay');
    }

    private function makeAssertion(string $email, array $overrides = []): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $payload = array_merge([
            'iss' => config('integrations.hokesen.issuer'),
            'aud' => config('integrations.hokesen.audience'),
            'sub' => 'hokesen-user-123',
            'email' => $email,
            'email_verified' => true,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
            'jti' => (string) Str::uuid(),
        ], $overrides);

        $encodedHeader = $this->base64UrlEncode((string) json_encode($header));
        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload));
        $signature = hash_hmac(
            'sha256',
            $encodedHeader.'.'.$encodedPayload,
            config('integrations.hokesen.shared_secret'),
            true
        );
        $encodedSignature = $this->base64UrlEncode($signature);

        return $encodedHeader.'.'.$encodedPayload.'.'.$encodedSignature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
