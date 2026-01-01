<?php

namespace Tests\Feature;

use App\Models\SessionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTemplatePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_any_templates(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('viewAny', SessionTemplate::class));
    }

    public function test_user_can_view_their_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $template));
    }

    public function test_user_can_view_system_template(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null]);

        $this->assertTrue($user->can('view', $systemTemplate));
    }

    public function test_user_cannot_view_another_users_template(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTemplate = SessionTemplate::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('view', $otherTemplate));
    }

    public function test_user_can_create_template(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', SessionTemplate::class));
    }

    public function test_user_can_update_their_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $template));
    }

    public function test_user_cannot_update_system_template(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null]);

        $this->assertFalse($user->can('update', $systemTemplate));
    }

    public function test_user_cannot_update_another_users_template(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTemplate = SessionTemplate::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('update', $otherTemplate));
    }

    public function test_user_can_modify_their_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('modify', $template));
    }

    public function test_user_can_modify_system_template(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null]);

        $this->assertTrue($user->can('modify', $systemTemplate));
    }

    public function test_user_cannot_modify_another_users_template(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTemplate = SessionTemplate::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('modify', $otherTemplate));
    }

    public function test_user_can_delete_their_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $template));
    }

    public function test_user_cannot_delete_system_template(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null]);

        $this->assertFalse($user->can('delete', $systemTemplate));
    }

    public function test_user_cannot_delete_another_users_template(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTemplate = SessionTemplate::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('delete', $otherTemplate));
    }
}
