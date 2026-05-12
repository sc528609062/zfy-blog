<?php

namespace Database\Seeders;

use App\Domain\Theme\ThemeManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        /** @var ThemeManager $tm */
        $tm = app(ThemeManager::class);

        foreach ($tm->all() as $theme) {
            DB::table('themes')->updateOrInsert(
                ['slug' => $theme->slug],
                [
                    'name'        => $theme->name,
                    'version'     => $theme->version,
                    'author'      => $theme->author,
                    'description' => $theme->description,
                    'compatible'  => $theme->compatible,
                    'preview'     => $theme->preview,
                    'active'      => $theme->slug === config('zfy.theme.default'),
                    'builtin'     => true,
                    'manifest'    => json_encode([
                        'menus'   => $theme->menus,
                        'regions' => $theme->regions,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'active_theme'],
            [
                'value'      => config('zfy.theme.default'),
                'type'       => 'string',
                'group'      => 'appearance',
                'autoload'   => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $tm->flushCache();
    }
}
