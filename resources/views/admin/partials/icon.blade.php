@php
    $icons = [
        'home'   => '<path stroke-linecap="round" stroke-linejoin="round" d="m3 12 9-9 9 9M5 10v10h14V10"/>',
        'doc'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-7 4h8a2 2 0 002-2V8l-6-6H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
        'plus'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
        'folder' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>',
        'tag'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8V4h4l13 13-4 4L3 8z"/><circle cx="7" cy="8" r="1"/>',
        'chat'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 20l1.5-3.5A8 8 0 1112 20H5l-2 0z"/>',
        'image'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18v14H3zm4 9 3-3 4 5 3-3 4 4"/>',
        'cart'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l3 13h11l2-9H6"/><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/>',
        'star'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/>',
        'brush'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 19c2 0 3-1 3-3 0-2-2-2-2-4 0-3 4-7 9-7s5 4 5 5c0 4-7 9-9 9-2 0-3 0-6 0z"/>',
        'menu'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18M3 18h18"/>',
        'layout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18v6H3zM3 14h8v6H3zm12 0h6v6h-6z"/>',
        'users'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 14a4 4 0 100-8 4 4 0 000 8zm0 2c-3 0-7 2-7 5h14c0-3-4-5-7-5zM7 11a3 3 0 100-6 3 3 0 000 6z"/>',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v6c0 5-3 8-8 9-5-1-8-4-8-9V6l8-3z"/>',
        'puzzle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 4h2a2 2 0 00-2 2 2 2 0 002 2v3h3a2 2 0 002-2 2 2 0 012 2v3h-3a2 2 0 00-2 2 2 2 0 002 2v3h-2a2 2 0 00-2-2 2 2 0 00-2 2H8v-3a2 2 0 00-2-2 2 2 0 01-2-2v-3h3a2 2 0 002-2 2 2 0 00-2-2V4h4z"/>',
        'cog'    => '<circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 12a7 7 0 00-.1-1l2-1.5-2-3.4-2.3 1a7 7 0 00-1.7-1L14.5 3h-5L9 5.1a7 7 0 00-1.7 1l-2.3-1-2 3.4 2 1.5a7 7 0 000 2L3 13.5 5 17l2.3-1a7 7 0 001.7 1l.5 2.4h5l.4-2.4a7 7 0 001.7-1l2.3 1 2-3.4-2-1.5a7 7 0 00.1-1z"/>',
    ];
    $svg = $icons[$name] ?? $icons['doc'];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svg !!}</svg>
