<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\SiteSettings;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function __invoke(Request $request, SiteSettings $settings)
    {
        $page = max(1, min(100000, $request->integer('page', 1)));
        $visible = $settings->get('reading.search_visible', true);
        $pages = $visible ? (int) ceil(Content::published()->count() / 1000) : 0;
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $index = ! $request->has('page') && $pages > 1;
        $xml->startElementNS(null, $index ? 'sitemapindex' : 'urlset', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        if ($index) {
            for ($number = 1; $number <= $pages; $number++) {
                $xml->startElement('sitemap');
                $xml->writeElement('loc', route('sitemap', ['page' => $number]));
                $xml->endElement();
            }
        } elseif ($visible) {
            foreach (Content::published()->orderBy('id')->forPage($page, 1000)->get(['slug', 'updated_at']) as $content) {
                $xml->startElement('url');
                $xml->writeElement('loc', route('contents.show', $content->slug));
                $xml->writeElement('lastmod', $content->updated_at->toAtomString());
                $xml->endElement();
            }
        }
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory())->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
