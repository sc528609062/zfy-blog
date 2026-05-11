<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use App\Models\Tag;
use App\Services\DemoContentRepository;
use App\Services\OrderService;
use App\Services\Payment\PaymentManager;
use App\Services\ThemeManager;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        private readonly ThemeManager $themes,
        private readonly DemoContentRepository $repository,
    ) {}

    public function home()
    {
        return $this->render('home');
    }

    public function channel(string $type)
    {
        return $this->render($type.'-channel', match ($type) {
            'posts' => 'post',
            'images' => 'images',
            'files' => 'files',
            default => null,
        });
    }

    public function category(Category $category)
    {
        return $this->render('category-page', null, ['currentCategory' => $category]);
    }

    public function tag(Tag $tag)
    {
        return $this->render('tag-page', null, ['currentTag' => $tag]);
    }

    public function content(string $slug)
    {
        $content = $this->repository->findContent($slug) ?? Content::where('status', 'published')->firstOrFail();
        $page = match ($content->type) {
            'files' => 'file-detail',
            'images' => 'images-detail',
            'page' => 'page-detail',
            default => 'content-detail',
        };

        return $this->render($page, null, ['content' => $content]);
    }

    public function page(string $slug)
    {
        $content = Content::where('type', 'page')->where('slug', $slug)->firstOrFail();

        return $this->render('page-detail', null, ['content' => $content]);
    }

    public function generic(string $page)
    {
        return $this->render($page);
    }

    public function buy(Request $request, string $slug, OrderService $orders, PaymentManager $payments)
    {
        $content = Content::where('slug', $slug)->firstOrFail();
        $order = $orders->createForContent($content, $request->user(), $request->string('gateway', 'alipay_official'));
        $payment = $order->total_amount > 0 ? $payments->createPayment($order, $order->pay_channel) : null;

        return view('payments.checkout', [
            'order' => $order->load('items'),
            'payment' => $payment,
            'theme' => $this->themes->active(),
        ]);
    }

    private function render(string $page, ?string $type = null, array $extra = [])
    {
        $theme = $this->themes->active();

        return view($theme['view'], array_merge(
            $this->repository->pageData($page, $type),
            $extra,
            ['theme' => $theme]
        ));
    }
}
