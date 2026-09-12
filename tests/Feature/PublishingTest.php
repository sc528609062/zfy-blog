<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Menu;
use App\Models\User;
use App\Services\DemoContentRepository;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreInstallSeeder::class);
    }

    public function test_category_filters_and_search_only_return_matching_content(): void
    {
        $category = Category::create(['name' => 'Docs', 'slug' => 'docs']);
        $match = Content::create(['type' => 'post', 'title' => 'Documentation', 'slug' => 'docs', 'status' => 'published', 'category_id' => $category->id]);
        Content::create(['type' => 'files', 'title' => 'Unrelated', 'slug' => 'other', 'status' => 'published']);
        $result = app(DemoContentRepository::class)->pageData('category-page', null, ['category_id' => $category->id]);
        $this->assertSame([$match->id], $result['contents']->modelKeys());
        $this->get('/search?q=Documentation')->assertOk()->assertSee('Documentation')->assertDontSee('Unrelated</h3>', false);
    }

    public function test_comment_submission_moderation_and_cross_content_reply_validation(): void
    {
        $user = User::factory()->create();
        $content = Content::create(['type' => 'post', 'title' => 'Article', 'slug' => 'article', 'status' => 'published']);
        $other = Content::create(['type' => 'post', 'title' => 'Other', 'slug' => 'other', 'status' => 'published']);
        $foreign = Comment::create(['content_id' => $other->id, 'user_id' => $user->id, 'body' => 'Other comment', 'status' => 'approved']);
        $this->actingAs($user)->post('/content/article/comments', ['body' => 'Awaiting review'])->assertRedirect();
        $this->assertSame(0, $content->fresh()->comment_count);
        $this->postJson('/content/article/comments', ['body' => 'Wrong parent', 'parent_id' => $foreign->id])->assertUnprocessable();
        $comment = Comment::where('body', 'Awaiting review')->first();
        $this->actingAs(User::where('username', 'admin')->first())->patchJson('/admin/resources/comments/'.$comment->id, ['body' => $comment->body, 'status' => 'approved'])->assertOk();
        $this->assertSame(1, $content->fresh()->comment_count);
        $this->get('/content/article')->assertOk()->assertSee('Awaiting review');
    }

    public function test_menu_rejects_unsafe_urls_and_renders_saved_items(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());
        $this->postJson('/admin/resources/menus', ['name' => 'Main', 'location' => 'primary', 'items' => [['title' => 'Bad', 'url' => 'javascript:alert(1)']]])->assertUnprocessable();
        $this->postJson('/admin/resources/menus', ['name' => 'Main', 'location' => 'primary', 'items' => [['title' => 'Documentation menu', 'url' => '/posts']]])->assertCreated();
        $this->assertSame('/posts', Menu::first()->items->first()->url);
        $this->get('/')->assertOk()->assertSee('Documentation menu');
    }

    public function test_user_center_uses_current_users_orders(): void
    {
        $this->get('/user')->assertRedirect('/login');
        $user = User::factory()->create(['name' => 'Account owner']);
        $this->actingAs($user)->get('/user')->assertOk()->assertSee('Account owner')->assertSee('暂无订单')->assertDontSee('风之旅人');
        foreach (['wallet', 'points', 'downloads', 'vip', 'settings', 'author'] as $page) {
            $this->get('/user/'.$page)->assertOk();
        }
    }
}
