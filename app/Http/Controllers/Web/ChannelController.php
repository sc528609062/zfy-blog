<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function posts(): View
    {
        return $this->renderChannel(Content::TYPE_POST, '文章频道');
    }

    public function images(): View
    {
        return $this->renderChannel(Content::TYPE_IMAGES, '图集频道');
    }

    public function files(): View
    {
        return $this->renderChannel(Content::TYPE_FILES, '资源频道');
    }

    protected function renderChannel(string $type, string $title): View
    {
        $contents = Content::query()
            ->published()
            ->ofType($type)
            ->with(['author', 'category', 'tags'])
            ->latest('published_at')
            ->paginate(20);

        return view('channel', [
            'title' => $title,
            'channelType' => $type,
            'contents' => $contents,
        ]);
    }
}
