<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteBanner;
use App\Models\WebsitePage;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicWebsiteController extends Controller
{
    /* ─────────────────────────────────────────────
     | GET /api/website/page/{key}?lang=en|ms
     ───────────────────────────────────────────── */
    public function page(Request $request, string $key)
    {
        $lang = $this->resolveLang($request);

        $data = Cache::remember("website_page_{$key}_{$lang}", 600, function () use ($key, $lang) {
            $page = WebsitePage::where('page_key', $key)
                ->where('is_active', true)
                ->with([
                    'banners' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                    'sections' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                    'sections.items' => fn ($q) => $q->orderBy('sort_order'),
                ])
                ->firstOrFail();

            return [
                'page' => [
                    'key'  => $page->page_key,
                    'name' => $this->pick($page, 'name', $lang),
                ],
                'banners' => $page->banners->map(fn ($b) => $this->formatBanner($b, $lang)),
                'sections' => $page->sections->map(fn ($s) => $this->formatSection($s, $lang)),
            ];
        });

        return response()->json(['status' => 'success', 'lang' => $lang, 'data' => $data]);
    }

    /* ─────────────────────────────────────────────
     | GET /api/website/banners/{pageKey}?lang=en|ms
     ───────────────────────────────────────────── */
    public function banners(Request $request, string $pageKey)
    {
        $lang = $this->resolveLang($request);

        $page = WebsitePage::where('page_key', $pageKey)
            ->where('is_active', true)
            ->firstOrFail();

        $banners = WebsiteBanner::where('page_id', $page->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'status' => 'success',
            'lang'   => $lang,
            'data'   => $banners->map(fn ($b) => $this->formatBanner($b, $lang, true)),
        ]);
    }

    /* ─────────────────────────────────────────────
     | GET /api/website/banner/{id}?lang=en|ms
     ───────────────────────────────────────────── */
    public function bannerDetail(Request $request, int $id)
    {
        $lang = $this->resolveLang($request);

        $banner = WebsiteBanner::where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'lang'   => $lang,
            'data'   => $this->formatBanner($banner, $lang, true),
        ]);
    }

    /* ─────────────────────────────────────────────
     | GET /api/website/settings?lang=en|ms
     ───────────────────────────────────────────── */
    public function settings(Request $request)
    {
        $lang = $this->resolveLang($request);

        $settings = WebsiteSetting::all()->mapWithKeys(function ($s) use ($lang) {
            return [$s->key => $this->pick($s, 'value', $lang)];
        });

        return response()->json(['status' => 'success', 'lang' => $lang, 'data' => $settings]);
    }

    /* ─────────────────────────────────────────────
     | Lang resolution — reused by every method above
     ───────────────────────────────────────────── */
    private function resolveLang(Request $request): string
    {
        $lang = strtolower($request->query('lang', 'en'));
        return in_array($lang, ['en', 'ms']) ? $lang : 'en';
    }

    /**
     * Picks {field}_ms if lang=ms AND it has a value, otherwise falls back to {field}_en.
     * This means Malay never shows blank just because a translation wasn't filled in yet.
     */
    private function pick($model, string $field, string $lang): ?string
    {
        if ($lang === 'ms') {
            $ms = $model->{"{$field}_ms"} ?? null;
            if (! empty($ms)) {
                return $ms;
            }
        }

        return $model->{"{$field}_en"} ?? null;
    }

    /* ─────────────────────────────────────────────
     | Helpers
     ───────────────────────────────────────────── */
    private function formatBanner(WebsiteBanner $b, string $lang, bool $withId = false): array
    {
        $data = [
            'title'          => $this->pick($b, 'title', $lang),
            'subtitle'       => $this->pick($b, 'subtitle', $lang),
            'description'    => $this->pick($b, 'description', $lang),
            'image'          => $b->image_url,
            'mobile_image'   => $b->mobile_image_url,
            'button_text'    => $this->pick($b, 'button_text', $lang),
            'button_url'     => $b->button_url,
            'sort_order'     => $b->sort_order,
        ];

        if ($withId) {
            $data = ['id' => $b->id] + $data;
        }

        return $data;
    }

    private function formatSection($s, string $lang): array
    {
        return [
            'id'          => $s->id,
            'key'         => $s->section_key,
            'type'        => $s->section_type,
            'icon'        => $s->icon,
            'image'       => $s->image_url,
            'title'       => $this->pick($s, 'title', $lang),
            'description' => $this->pick($s, 'description', $lang),
            'label'       => $this->pick($s, 'label', $lang),
            'value'       => $this->pick($s, 'value', $lang),
            'items'       => $s->items->map(fn ($i) => [
                'id'          => $i->id,
                'icon'        => $i->icon,
                'image'       => $i->image_url,
                'value'       => $this->pick($i, 'value', $lang),
                'label'       => $this->pick($i, 'label', $lang),
                'description' => $this->pick($i, 'description', $lang),
            ]),
        ];
    }
}