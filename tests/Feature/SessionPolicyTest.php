<?php

namespace Tests\Feature;

use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_any_sessions(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('viewAny', Session::class));
    }

    public function test_user_can_view_their_own_session(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $session));
    }

    public function test_user_cannot_view_another_users_session(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherSession = Session::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('view', $otherSession));
    }

    public function test_user_can_create_session(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Session::class));
    }

    public function test_user_can_update_their_own_session(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $session));
    }

    public function test_user_cannot_update_another_users_session(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherSession = Session::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('update', $otherSession));
    }

    public function test_user_can_delete_their_own_session(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $session));
    }

    public function test_user_cannot_delete_another_users_session(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherSession = Session::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('delete', $otherSession));
    }
}
