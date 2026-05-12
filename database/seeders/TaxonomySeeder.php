<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '游戏资源', 'slug' => 'games',    'icon' => 'gamepad',  'description' => '游戏 / Mod / 补丁'],
            ['name' => '设计素材', 'slug' => 'design',   'icon' => 'palette',  'description' => 'UI / 图集 / PSD'],
            ['name' => '影视音乐', 'slug' => 'media',    'icon' => 'film',     'description' => '电影 / 音乐 / 周边'],
            ['name' => '软件工具', 'slug' => 'software', 'icon' => 'tool',     'description' => '生产力工具 / 实用软件'],
            ['name' => '学习教程', 'slug' => 'learning', 'icon' => 'book',     'description' => '教程 / 课程 / 干货'],
            ['name' => '生活杂谈', 'slug' => 'life',     'icon' => 'coffee',   'description' => '随笔 / 日常'],
        ];

        foreach ($categories as $i => $row) {
            Category::updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, ['sort_order' => $i, 'parent_id' => null])
            );
        }
    }
}
