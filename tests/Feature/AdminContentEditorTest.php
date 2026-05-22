<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Database\Seeders\CoreInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_save_markdown_draft(): void
    {
        $admin = $this->seedAndAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.contents.store'), [
                'title' => 'Markdown 草稿',
                'type' => 'post',
                'status' => 'draft',
                'markdown_cache' => '**Hello**',
            ])
            ->assertOk()
            ->assertJsonPath('content.status', 'draft');

        $content = Content::firstOrFail();

        $this->assertSame('Markdown 草稿', $content->title);
        $this->assertSame('draft', $content->status);
        $this->assertNull($content->published_at);
        $this->assertStringContainsString('<strong>Hello</strong>', $content->rendered_html);
        $this->assertSame('markdown', $content->block_json['mode']);
    }

    public function test_publishing_sets_published_at_and_unique_slug(): void
    {
        $admin = $this->seedAndAdmin();

        Content::create([
            'author_id' => $admin->id,
            'type' => 'post',
            'status' => 'draft',
            'title' => 'Hello World',
            'slug' => 'hello-world',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.contents.store'), [
                'title' => 'Hello World',
                'type' => 'post',
                'status' => 'published',
                'markdown_cache' => '# Title',
            ])
            ->assertOk()
            ->assertJsonPath('content.slug', 'hello-world-2')
            ->assertJsonPath('content.status', 'published');

        $content = Content::where('slug', 'hello-world-2')->firstOrFail();

        $this->assertNotNull($content->published_at);
    }

    public function test_unauthorized_user_cannot_save_content(): void
    {
        $this->seed(CoreInstallSeeder::class);
        $user = User::factory()->create([
            'username' => 'reader',
        ]);
        $user->assignRole('USER');

        $this->actingAs($user)
            ->postJson(route('admin.contents.store'), [
                'title' => 'No Permission',
                'type' => 'post',
                'status' => 'draft',
                'markdown_cache' => 'content',
            ])
            ->assertForbidden();
    }

    public function test_preview_renders_markdown_shortcodes_as_article_components(): void
    {
        $admin = $this->seedAndAdmin();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), [
                'markdown' => implode("\n", [
                    '**加粗文字**',
                    '*斜体文字*',
                    '- [ ] 待办事项',
                    '{zfy-alert type="info" title="提示"}',
                    '提示内容',
                    '{/zfy-alert}',
                    '{zfy-progress value="60" /}',
                    '{zfy-cloud title="下载资源" url="https://example.com" /}',
                    '{zfy-tabs}',
                    '标签内容',
                    '{/zfy-tabs}',
                    '{zfy-mp3 title="音频" url="/audio/demo.mp3" /}',
                    '{zfy-button title="访问链接" url="https://example.com" /}',
                    '{zfy-timeline}',
                    '- 2026-05-13：版本发布',
                    '{/zfy-timeline}',
                    '{zfy-grid}',
                    '宫格项目',
                    '{/zfy-grid}',
                    '{zfy-hide title="登录后可见"}',
                    '隐藏内容',
                    '{/zfy-hide}',
                    '{zfy-lamp title="灵感提示" /}',
                    ':smile: :rocket: :sparkles:',
                ]),
            ])
            ->assertOk();

        $html = $response->json('html');

        $this->assertStringContainsString('<strong>加粗文字</strong><br', $html);
        $this->assertStringContainsString('zfy-taskbox', $html);
        $this->assertStringContainsString('zfy-shortcode-alert', $html);
        $this->assertStringContainsString('zfy-shortcode-progress', $html);
        $this->assertStringContainsString('zfy-shortcode-cloud', $html);
        $this->assertStringContainsString('zfy-shortcode-tabs', $html);
        $this->assertStringContainsString('zfy-shortcode-media-mp3', $html);
        $this->assertStringContainsString('zfy-shortcode-button', $html);
        $this->assertStringContainsString('zfy-shortcode-timeline', $html);
        $this->assertStringContainsString('zfy-shortcode-grid', $html);
        $this->assertStringContainsString('zfy-shortcode-hide', $html);
        $this->assertStringContainsString('zfy-shortcode-lamp', $html);
        $this->assertStringNotContainsString('{zfy-alert', $html);
    }

    public function test_preview_matches_editor_line_breaks_lists_and_zfy_shortcodes(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
开始测试编辑器
**加粗文字**
*斜体文字*
~~删除文字~~
`code`
# 一级标题
## 二级标题
### 三级标题
#### 四级标题
##### 五级标题
###### 六级标题
> 引用内容
1. 列表项目
2. 列表项目2
- 列表项目
- 列表项目2
- [ ] 待办事项
- [x] 已完成事项

---
[链接文字](https://example.com)
![图片描述](/assets/zfy/placeholders/blue.svg)
| 标题 | 内容 |
| --- | --- |
| 示例 | 文本 |
```
代码内容
```
{zfy-html}
<div class="zfy-custom-html">HTML 内容</div>
{/zfy-html}
{zfy-time format="YYYY-MM-DD HH:mm:ss" /}
★ ☆ ✓ ✕ → ← ↑ ↓
:smile: :rocket: :sparkles:
{zfy-alert type="info" title="提示"}
提示内容
{/zfy-alert}
{zfy-callout type="success" title="重点"}
重点内容
{/zfy-callout}
{zfy-mtitle title="小标题"}
标题说明
{/zfy-mtitle}
{zfy-card-default title="卡片标题"}
卡片内容
{/zfy-card-default}
{zfy-message type="warning"}
消息内容
{/zfy-message}
{zfy-progress value="60" /}
{zfy-collapse title="展开查看"}
折叠内容
{/zfy-collapse}
{zfy-tabs}
标签内容
{/zfy-tabs}
{zfy-bilibili title="视频" url="https://www.bilibili.com" /}
{zfy-mp3 title="音频" url="/audio/demo.mp3" /}
{zfy-cloud title="下载资源" url="https://example.com" /}
{zfy-button title="访问链接" url="https://example.com" /}
{zfy-timeline}
- 2026-05-13：版本发布
{/zfy-timeline}
{zfy-copy title="复制内容"}
可复制文本
{/zfy-copy}
{zfy-lamp title="灵感提示" /}
{zfy-grid}
宫格项目
{/zfy-grid}
{zfy-hide title="登录后可见"}
隐藏内容
{/zfy-hide}
MARKDOWN;

        $response = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk();

        $html = $response->json('html');

        $this->assertStringContainsString('开始测试编辑器<br', $html);
        $this->assertStringContainsString('<strong>加粗文字</strong><br', $html);
        $this->assertStringContainsString('<em>斜体文字</em><br', $html);
        $this->assertStringContainsString('<del>删除文字</del><br', $html);
        $this->assertStringContainsString('zfy-shortcode-quote', $html);
        $this->assertStringContainsString('quote_q', $html);
        $this->assertStringContainsString('data-color="#af870d"', $html);
        $this->assertStringNotContainsString('</blockquote><br', $html);
        $this->assertStringNotContainsString('<blockquote>', $html);
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>列表项目</li>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('待办事项', $html);
        $this->assertStringContainsString('zfy-taskbox is-checked', $html);
        $this->assertStringContainsString('wp-block-zibllblock-enlighter', $html);
        $this->assertStringContainsString('enlighter-toolbar', $html);
        $this->assertStringContainsString('enlighter-btn-raw', $html);
        $this->assertStringContainsString('enlighter-btn-copy', $html);
        $this->assertStringContainsString('enlighter-btn-window', $html);
        $this->assertStringContainsString('enlighter-raw', $html);
        $this->assertStringContainsString('enlighter-origin', $html);
        $this->assertStringContainsString('zfy-shortcode-alert', $html);
        $this->assertStringContainsString('zfy-shortcode-callout', $html);
        $this->assertStringContainsString('zfy-shortcode-collapse', $html);
        $this->assertStringContainsString('zfy-shortcode-tabs', $html);
        $this->assertStringContainsString('zfy-shortcode-audio', $html);
        $this->assertStringContainsString('zfy-shortcode-timeline', $html);
        $this->assertStringContainsString('zfy-shortcode-grid', $html);
        $this->assertStringContainsString('zfy-custom-html', $html);
        $this->assertStringContainsString('zfy-shortcode-time', $html);
        $this->assertStringContainsString('data-zfy-time-format="YYYY-MM-DD HH:mm:ss"', $html);
        $this->assertMatchesRegularExpression('/<div class="zfy-shortcode zfy-shortcode-time"[^>]*>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}<\/div>/', $html);
        $this->assertStringNotContainsString('{zfy-', $html);
    }

    public function test_fenced_code_blocks_render_with_zibll_enlighter_shell_and_escape_html(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
```html
<div class="da-copyright"><span data-x="1">NOTICE</span></div>
<script>alert(1)</script>
```
MARKDOWN;

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('pre class="wp-block-zibllblock-enlighter"', $html);
        $this->assertStringContainsString('enlighter-default enlighter-v-standard enlighter-t-enlighter enlighter-hover enlighter-linenumbers enlighter-overflow-scroll', $html);
        $this->assertStringContainsString('enlighter-toolbar', $html);
        $this->assertStringContainsString('enlighter-btn-raw', $html);
        $this->assertStringContainsString('enlighter-btn-copy', $html);
        $this->assertStringContainsString('enlighter-btn-window', $html);
        $this->assertStringContainsString('enlighter-raw', $html);
        $this->assertStringContainsString('gl enlighter-origin', $html);
        $this->assertStringContainsString('data-enlighter-language="html"', $html);
        $this->assertStringContainsString('<div class="enlighter" style=""><div class=""><div><span class="enlighter-g1">&lt;</span>', $html);
        $this->assertStringNotContainsString('<div class="enlighter" style=""><div class=""><div class="">', $html);
        $this->assertStringContainsString('&lt;div class="da-copyright"&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<pre><code', $html);
    }

    public function test_preview_normalizes_legacy_storage_media_urls_to_media_path(): void
    {
        $admin = $this->seedAndAdmin();
        $legacyUrl = url('/storage/media/editor/images/2026/05/demo.png');

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), [
                'markdown' => "![旧图片]({$legacyUrl})",
            ])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString(url('/media/editor/images/2026/05/demo.png'), $html);
        $this->assertStringNotContainsString('/storage/media/', $html);
    }

    public function test_joe_style_child_shortcodes_render_with_zfy_names(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
{zfy-tabs}
{zfy-tabs-pane label="标签一"}
标签一内容
{/zfy-tabs-pane}
{zfy-tabs-pane label="标签二"}
标签二内容
{/zfy-tabs-pane}
{/zfy-tabs}
{zfy-collapse}
{zfy-collapse-item label="折叠标题一" open}
折叠内容一
{/zfy-collapse-item}
{zfy-collapse-item label="折叠标题二"}
折叠内容二
{/zfy-collapse-item}
{/zfy-collapse}
{zfy-timeline}
{zfy-timeline-item color="#19be6b"}
2026-05-13：版本发布
{/zfy-timeline-item}
{/zfy-timeline}
{zfy-grid column="3"}
{zfy-grid-item}
宫格项目一
{/zfy-grid-item}
{zfy-grid-item}
宫格项目二
{/zfy-grid-item}
{/zfy-grid}
{zfy-copy title="复制按钮" copyText="复制内容" /}
{zfy-bilibili title="视频" bvid="BV1xx411c7mD" page="1" /}
MARKDOWN;

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('标签一内容', $html);
        $this->assertStringContainsString('标签二内容', $html);
        $this->assertStringContainsString('zfy-collapse-item is-open', $html);
        $this->assertStringContainsString('折叠标题二', $html);
        $this->assertStringContainsString('zfy-timeline-dot', $html);
        $this->assertStringContainsString('zfy-grid-columns-3', $html);
        $this->assertStringContainsString('复制内容', $html);
        $this->assertStringContainsString('zfy-bilibili-frame', $html);
        $this->assertStringNotContainsString('{zfy-tabs-pane', $html);
        $this->assertStringNotContainsString('{zfy-collapse-item', $html);
        $this->assertStringNotContainsString('{zfy-timeline-item', $html);
        $this->assertStringNotContainsString('{zfy-grid-item', $html);
    }

    public function test_quote_blocks_support_zibll_style_structure_and_custom_colors(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
> [color=#19be6b] 绿色引用

{zfy-quote color="#af870d"}
**子比主题V8** 更新内容
{/zfy-quote}

{quote color="#2d8cf0"}
Joe 原名兼容引用
{/quote}
MARKDOWN;

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->json('html');

        $this->assertSame(3, substr_count($html, 'zfy-shortcode-quote'));
        $this->assertStringContainsString('quote_q', $html);
        $this->assertStringContainsString('class="fa fa-quote-left"', $html);
        $this->assertStringContainsString('data-color="#19be6b"', $html);
        $this->assertStringContainsString('--quote-color:#19be6b', $html);
        $this->assertStringContainsString('data-color="#af870d"', $html);
        $this->assertStringContainsString('data-color="#2d8cf0"', $html);
        $this->assertStringContainsString('<strong>子比主题V8</strong>', $html);
        $this->assertStringNotContainsString('[color=#19be6b]', $html);
        $this->assertStringNotContainsString('{zfy-quote', $html);
        $this->assertStringNotContainsString('{quote', $html);
    }

    public function test_joe_original_shortcodes_are_accepted_as_compatibility_aliases(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
{alert type="info"}
警告提示
{/alert}
{callout color="#f0ad4e"}
标注内容
{/callout}
{mtitle title="居中标题"/}
{dplayer src="/video/demo.mp4"/}
{bilibili bvid="BV1xx411c7mD" page="1"/}
{music-list id="123" color="#1989fa"/}
{music id="456" color="#1989fa"/}
{mp3 name="音频名称" url="/audio/demo.mp3" cover="/assets/zfy/placeholders/blue.svg" theme="#f0ad4e"/}
{abtn icon="fa-download" color="#ff6800" href="https://example.com" radius="8px" content="按钮内容"/}
{anote icon="fa-download" href="https://example.com" type="secondary" content="便条内容"/}
{dotted startColor="#ff6c6c" endColor="#1989fa"/}
{hide}
隐藏内容
{/hide}
{card-default label="卡片标题" width="100%"}
卡片内容
{/card-default}
{message type="success" content="消息内容"/}
{progress percentage="80" color="#ff6c6c"/}
{tabs}
{tabs-pane label="标签一"}
标签一内容
{/tabs-pane}
{/tabs}
{card-list}
{card-list-item}
列表一内容
{/card-list-item}
{/card-list}
{timeline}
{timeline-item color="#19be6b"}
正式上线
{/timeline-item}
{/timeline}
{copy showText="显示文案" copyText="复制内容"/}
{card-describe title="卡片描述"}
卡片内容
{/card-describe}
{lamp/}
{collapse}
{collapse-item label="折叠标题一" open}
折叠内容一
{/collapse-item}
{/collapse}
{cloud title="显示标题" type="default" url="https://example.com" password="1234"/}
{gird column="3" gap="15"}
{gird-item}
宫格内容
{/gird-item}
{/gird}
MARKDOWN;

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('zfy-shortcode-alert', $html);
        $this->assertStringContainsString('zfy-shortcode-callout', $html);
        $this->assertStringContainsString('zfy-shortcode-video', $html);
        $this->assertStringContainsString('zfy-shortcode-bilibili', $html);
        $this->assertStringContainsString('zfy-shortcode-media-music-list', $html);
        $this->assertStringContainsString('zfy-shortcode-audio', $html);
        $this->assertStringContainsString('zfy-media-cover', $html);
        $this->assertStringContainsString('zfy-shortcode-note', $html);
        $this->assertStringContainsString('zfy-shortcode-dotted', $html);
        $this->assertStringContainsString('zfy-shortcode-card-list', $html);
        $this->assertStringContainsString('zfy-shortcode-card-describe', $html);
        $this->assertStringContainsString('zfy-grid-columns-3', $html);
        $this->assertStringNotContainsString('{alert', $html);
        $this->assertStringNotContainsString('{tabs-pane', $html);
        $this->assertStringNotContainsString('{gird', $html);
    }

    public function test_preview_and_save_clean_unsafe_html_and_run_filters(): void
    {
        $admin = $this->seedAndAdmin();

        zfy_filter('zfy_rendered_html', fn (string $html) => $html.'<p>filtered</p>');
        zfy_filter('zfy_content_payload', function (array $attributes) {
            $attributes['excerpt'] = 'filtered excerpt';

            return $attributes;
        });

        $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), [
                'markdown' => "{zfy-html}\n<script>alert(1)</script><strong>ok</strong>\n{/zfy-html}",
            ])
            ->assertOk()
            ->assertJsonMissing(['html' => '<script>alert(1)</script>'])
            ->assertSee('filtered', false);

        $this->actingAs($admin)
            ->postJson(route('admin.contents.store'), [
                'title' => 'Filtered',
                'type' => 'post',
                'status' => 'draft',
                'markdown_cache' => "{zfy-html}\n<script>alert(1)</script><strong>ok</strong>\n{/zfy-html}",
            ])
            ->assertOk();

        $content = Content::firstOrFail();

        $this->assertSame('filtered excerpt', $content->excerpt);
        $this->assertStringNotContainsString('<script>', $content->rendered_html);
        $this->assertStringContainsString('<strong>ok</strong>', $content->rendered_html);
        $this->assertStringContainsString('filtered', $content->rendered_html);
    }

    public function test_preview_and_save_escape_unmarked_raw_html_with_line_breaks(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
<p align="center">居中</p>
<p align="right">居右</p>
<font size="5" color="red">颜色大小</font>
MARKDOWN;

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('&lt;p align="center"&gt;居中&lt;/p&gt;', $html);
        $this->assertStringContainsString('&lt;p align="right"&gt;居右&lt;/p&gt;', $html);
        $this->assertStringContainsString('&lt;font size="5" color="red"&gt;颜色大小&lt;/font&gt;', $html);
        $this->assertStringContainsString('<br', $html);
        $this->assertStringNotContainsString('<p align="center">居中</p>', $html);
        $this->assertStringNotContainsString('<font size="5" color="#FF0000">颜色大小</font>', $html);

        $this->actingAs($admin)
            ->postJson(route('admin.contents.store'), [
                'title' => 'HTML 文本',
                'type' => 'post',
                'status' => 'draft',
                'markdown_cache' => $markdown,
            ])
            ->assertOk();

        $content = Content::firstOrFail();

        $this->assertStringContainsString('&lt;p align="center"&gt;居中&lt;/p&gt;', $content->rendered_html);
        $this->assertStringContainsString('&lt;p align="right"&gt;居右&lt;/p&gt;', $content->rendered_html);
        $this->assertStringContainsString('&lt;font size="5" color="red"&gt;颜色大小&lt;/font&gt;', $content->rendered_html);
        $this->assertFalse($content->block_json['raw_html']);
    }

    public function test_preview_and_save_render_marked_html_blocks(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
{zfy-html}
<p align="center">居中</p>
<p align="right">居右</p>
<font size="5" color="red">颜色大小</font>
{/zfy-html}
MARKDOWN;

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('<p align="center">居中</p>', $html);
        $this->assertStringContainsString('<p align="right">居右</p>', $html);
        $this->assertStringContainsString('<font size="5" color="#FF0000">颜色大小</font>', $html);
        $this->assertStringNotContainsString('{zfy-html}', $html);

        $this->actingAs($admin)
            ->postJson(route('admin.contents.store'), [
                'title' => 'HTML 排版',
                'type' => 'post',
                'status' => 'draft',
                'markdown_cache' => $markdown,
            ])
            ->assertOk();

        $content = Content::firstOrFail();

        $this->assertStringContainsString('<p align="center">居中</p>', $content->rendered_html);
        $this->assertStringContainsString('<p align="right">居右</p>', $content->rendered_html);
        $this->assertStringContainsString('<font size="5" color="#FF0000">颜色大小</font>', $content->rendered_html);
        $this->assertTrue($content->block_json['raw_html']);
    }

    public function test_time_shortcode_renders_live_block_with_selected_format(): void
    {
        $admin = $this->seedAndAdmin();

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), [
                'markdown' => '{zfy-time format="HH:mm:ss" /}',
            ])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('class="zfy-shortcode zfy-shortcode-time"', $html);
        $this->assertStringContainsString('data-zfy-time-format="HH:mm:ss"', $html);
        $this->assertMatchesRegularExpression('/>\d{2}:\d{2}:\d{2}<\/div>/', $html);
    }

    public function test_indent_toolbar_prefix_renders_as_indented_text_not_code_block(): void
    {
        $admin = $this->seedAndAdmin();

        $this->actingAs($admin)
            ->get('/admin/editor')
            ->assertOk()
            ->assertSee('"id":"indent"', false)
            ->assertSee('"prefix":"\u0026emsp;\u0026emsp;"', false);

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), [
                'markdown' => '&emsp;&emsp;缩进内容',
            ])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString("\u{2003}\u{2003}缩进内容", $html);
        $this->assertStringNotContainsString('<pre', $html);
        $this->assertStringNotContainsString('<code', $html);
    }

    public function test_marked_html_followed_by_legacy_time_label_renders_time_as_block(): void
    {
        $admin = $this->seedAndAdmin();
        $markdown = <<<'MARKDOWN'
{zfy-html}
<div class="zfy-custom-html">HTML 内容</div>
<p align="center">居中</p>
<p align="right">居右</p>
<font size="5" color="red">颜色大小</font>
{/zfy-html}
{zfy-time label="2026-05-22 13:39" /}
MARKDOWN;

        $html = $this->actingAs($admin)
            ->postJson(route('admin.contents.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('<font size="5" color="#FF0000">颜色大小</font>', $html);
        $this->assertStringContainsString('<div class="zfy-shortcode zfy-shortcode-time" data-zfy-time-format="YYYY-MM-DD HH:mm:ss">', $html);
        $this->assertStringNotContainsString('{zfy-time', $html);
    }

    public function test_contents_table_exposes_edit_url_and_editor_loads_existing_content(): void
    {
        $admin = $this->seedAndAdmin();
        $content = Content::create([
            'author_id' => $admin->id,
            'type' => 'post',
            'status' => 'draft',
            'title' => '可编辑文章',
            'slug' => 'editable-post',
            'markdown_cache' => '编辑内容',
            'rendered_html' => '<p>编辑内容</p>',
        ]);

        $this->actingAs($admin)
            ->get('/admin/contents')
            ->assertOk()
            ->assertSee('editor?content='.$content->id, false);

        $this->actingAs($admin)
            ->get('/admin/editor?content='.$content->id)
            ->assertOk()
            ->assertSee('"id":'.$content->id, false)
            ->assertSee('editable-post', false);
    }

    public function test_front_content_rerenders_stale_shortcode_cache_from_markdown(): void
    {
        $admin = $this->seedAndAdmin();
        $content = Content::create([
            'author_id' => $admin->id,
            'type' => 'post',
            'status' => 'published',
            'title' => '旧缓存兼容文章',
            'slug' => 'legacy-joe-cache',
            'published_at' => now(),
            'markdown_cache' => <<<'MARKDOWN'
{alert type="info"}
警告提示
{/alert}
{tabs}
{tabs-pane label="标签一"}
标签一内容
{/tabs-pane}
{/tabs}
{gird column="3"}
{gird-item}
宫格内容
{/gird-item}
{/gird}
MARKDOWN,
            'rendered_html' => '<p>{alert type="info"}警告提示{/alert}</p><p>{tabs}{tabs-pane label="标签一"}标签一内容{/tabs-pane}{/tabs}</p><p>{gird column="3"}宫格内容{/gird}</p>',
        ]);

        $this->get('/content/legacy-joe-cache')
            ->assertOk()
            ->assertSee('zfy-shortcode-alert', false)
            ->assertSee('zfy-shortcode-tabs', false)
            ->assertSee('zfy-shortcode-grid', false)
            ->assertDontSee('{alert', false)
            ->assertDontSee('{tabs', false)
            ->assertDontSee('{gird', false);

        $content->refresh();

        $this->assertStringContainsString('zfy-shortcode-alert', $content->rendered_html);
        $this->assertStringNotContainsString('{alert', $content->rendered_html);
    }

    public function test_front_content_rerenders_stale_blockquote_cache(): void
    {
        $admin = $this->seedAndAdmin();
        $content = Content::create([
            'author_id' => $admin->id,
            'type' => 'post',
            'status' => 'published',
            'title' => '旧引用缓存文章',
            'slug' => 'legacy-blockquote-cache',
            'published_at' => now(),
            'markdown_cache' => '> [color=#af870d] 引用内容',
            'rendered_html' => '<blockquote><p>引用内容</p></blockquote>',
        ]);

        $this->get('/content/legacy-blockquote-cache')
            ->assertOk()
            ->assertSee('zfy-shortcode-quote', false)
            ->assertSee('data-color="#af870d"', false)
            ->assertDontSee('<blockquote>', false);

        $content->refresh();

        $this->assertStringContainsString('zfy-shortcode-quote', $content->rendered_html);
        $this->assertStringNotContainsString('<blockquote>', $content->rendered_html);
    }

    public function test_front_content_rerenders_stale_plain_code_cache(): void
    {
        $admin = $this->seedAndAdmin();
        $content = Content::create([
            'author_id' => $admin->id,
            'type' => 'post',
            'status' => 'published',
            'title' => '旧代码缓存文章',
            'slug' => 'legacy-code-cache',
            'published_at' => now(),
            'markdown_cache' => <<<'MARKDOWN'
```php
echo "legacy";
```
MARKDOWN,
            'rendered_html' => '<pre><code>echo "legacy";</code></pre>',
        ]);

        $this->get('/content/legacy-code-cache')
            ->assertOk()
            ->assertSee('wp-block-zibllblock-enlighter', false)
            ->assertSee('enlighter-toolbar', false)
            ->assertDontSee('<pre><code>', false);

        $content->refresh();

        $this->assertStringContainsString('wp-block-zibllblock-enlighter', $content->rendered_html);
        $this->assertStringNotContainsString('<pre><code>', $content->rendered_html);
    }

    public function test_front_content_escapes_unmarked_raw_html_examples_as_text(): void
    {
        $admin = $this->seedAndAdmin();
        $content = Content::create([
            'author_id' => $admin->id,
            'type' => 'post',
            'status' => 'published',
            'title' => 'HTML 文本示例',
            'slug' => 'html-text-example',
            'published_at' => now(),
            'markdown_cache' => <<<'MARKDOWN'
<p align="center">居中</p>
<p align="right">居右</p>
<font size="5" color="red">颜色大小</font>
<button aria-disabled="false" type="button" class="el-button el-button--default zfy-editor-tool">按钮</button>
MARKDOWN,
            'rendered_html' => '<p align="center">居中</p><p align="right">居右</p><font size="5" color="red">颜色大小</font><button aria-disabled="false" type="button" class="el-button el-button--default zfy-editor-tool">按钮</button>',
        ]);

        $this->get('/content/html-text-example')
            ->assertOk()
            ->assertSee('&lt;p align="center"&gt;居中&lt;/p&gt;', false)
            ->assertSee('&lt;p align="right"&gt;居右&lt;/p&gt;', false)
            ->assertSee('&lt;font size="5" color="red"&gt;颜色大小&lt;/font&gt;', false)
            ->assertSee('&lt;button aria-disabled="false" type="button" class="el-button el-button--default zfy-editor-tool"&gt;按钮&lt;/button&gt;', false)
            ->assertDontSee('<p align="center">居中</p>', false)
            ->assertDontSee('<font size="5" color="#FF0000">颜色大小</font>', false)
            ->assertDontSee('<button aria-disabled="false" type="button" class="el-button el-button--default zfy-editor-tool">按钮</button>', false);

        $content->refresh();

        $this->assertStringContainsString('&lt;p align="center"&gt;居中&lt;/p&gt;', $content->rendered_html);
        $this->assertStringContainsString('&lt;p align="right"&gt;居右&lt;/p&gt;', $content->rendered_html);
        $this->assertStringContainsString('&lt;font size="5" color="red"&gt;颜色大小&lt;/font&gt;', $content->rendered_html);
        $this->assertStringContainsString('&lt;button aria-disabled="false" type="button" class="el-button el-button--default zfy-editor-tool"&gt;按钮&lt;/button&gt;', $content->rendered_html);
    }

    public function test_front_content_renders_marked_html_blocks(): void
    {
        $admin = $this->seedAndAdmin();
        $content = Content::create([
            'author_id' => $admin->id,
            'type' => 'post',
            'status' => 'published',
            'title' => 'HTML 渲染块',
            'slug' => 'html-render-block',
            'published_at' => now(),
            'markdown_cache' => <<<'MARKDOWN'
{zfy-html}
<p align="center">居中</p>
<p align="right">居右</p>
<font size="5" color="red">颜色大小</font>
{/zfy-html}
MARKDOWN,
            'rendered_html' => '{zfy-html}<p align="center">居中</p>{/zfy-html}',
        ]);

        $this->get('/content/html-render-block')
            ->assertOk()
            ->assertSee('<p align="center">居中</p>', false)
            ->assertSee('<p align="right">居右</p>', false)
            ->assertSee('<font size="5" color="#FF0000">颜色大小</font>', false)
            ->assertDontSee('{zfy-html}', false);

        $content->refresh();

        $this->assertStringContainsString('<p align="center">居中</p>', $content->rendered_html);
        $this->assertStringContainsString('<p align="right">居右</p>', $content->rendered_html);
        $this->assertStringContainsString('<font size="5" color="#FF0000">颜色大小</font>', $content->rendered_html);
        $this->assertStringNotContainsString('{zfy-html}', $content->rendered_html);
    }

    private function seedAndAdmin(): User
    {
        $this->seed(CoreInstallSeeder::class);

        return User::where('email', 'admin@zfy-blog.test')->firstOrFail();
    }
}
