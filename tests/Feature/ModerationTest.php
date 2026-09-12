<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_rule_applies_to_comments_and_rejects_without_writing(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create();
        $content = Content::create(['title' => 'Example', 'slug' => 'example', 'type' => 'post', 'status' => 'published']);
        foreach (['discussion.require_approval' => false, 'discussion.sensitive_words' => 'restricted-word', 'discussion.sensitive_action' => 'review'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => ['raw' => $value]]);
        }
        $this->actingAs($user)->postJson('/api/v1/contents/example/comments', ['body' => 'restricted-word'])->assertSuccessful();
        $this->assertDatabaseHas('comments', ['body' => 'restricted-word', 'status' => 'pending']);
        Setting::where('key', 'discussion.sensitive_action')->update(['value' => ['raw' => 'reject']]);
        $this->postJson('/api/v1/contents/example/comments', ['body' => 'restricted-word again'])->assertUnprocessable();
        $this->assertDatabaseCount('comments', 1);
    }
}
