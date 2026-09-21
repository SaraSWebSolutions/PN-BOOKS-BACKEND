<?php

namespace App\Http\Controllers;

use App\Models\WebsiteBanner;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Models\WebsiteSetting;
use App\Models\WebsiteSectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;


class WebsiteContentController extends Controller
{
    
const UPLOAD_DIR      = 'uploads/website';                        // project root / uploads/website/...
const ALLOWED_FOLDERS = ['banners', 'sections', 'section-items'];
    public function index()
    {
        $pages = WebsitePage::orderBy('name_en')->get();
        return view('website.index', compact('pages'));
    }

    public function show(WebsitePage $page)
    {
        $page->load(['banners', 'sections']);
        return view('website.show', compact('page'));
    }

    public function storePage(Request $request)
{
    $data = $request->validate([
        'page_key'  => 'required|string|max:100|unique:website_pages,page_key',
        'name_en'   => 'required|string|max:150',
        'name_ms'   => 'nullable|string|max:150',
    ]);

    try {
        // ✅ enforce a clean, URL-safe key even if admin types "PI Readz"
        $data['page_key'] = Str::slug($data['page_key']);

        $page = WebsitePage::create([
            'page_key'  => $data['page_key'],
            'name_en'   => $data['name_en'],
            'name_ms'   => $data['name_ms'] ?? null,
            'is_active' => true,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Page created', 'page' => $page]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}
    /* ───────────── Banners ───────────── */

public function storeBanner(Request $request, WebsitePage $page)
{
    $data = $request->validate([
        'banner_id'        => 'nullable|exists:website_banners,id',
        'title_en'         => 'nullable|string|max:200',
        'title_ms'         => 'nullable|string|max:200',
        'subtitle_en'      => 'nullable|string|max:255',
        'subtitle_ms'      => 'nullable|string|max:255',
        'description_en'   => 'nullable|string',
        'description_ms'   => 'nullable|string',
        'button_text_en'   => 'nullable|string|max:100',
        'button_text_ms'   => 'nullable|string|max:100',
        'button_url'       => 'nullable|string|max:255',
        'sort_order'       => 'nullable|integer',
        'is_active'        => 'nullable|boolean',
        'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'mobile_image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
    ]);

    try {
        $banner = ! empty($data['banner_id'])
            ? WebsiteBanner::findOrFail($data['banner_id'])
            : new WebsiteBanner();

        $banner->fill([
            'page_id'         => $page->id,
            'title_en'        => $data['title_en'] ?? null,
            'title_ms'        => $data['title_ms'] ?? null,
            'subtitle_en'     => $data['subtitle_en'] ?? null,
            'subtitle_ms'     => $data['subtitle_ms'] ?? null,
            'description_en'  => $data['description_en'] ?? null,
            'description_ms'  => $data['description_ms'] ?? null,
            'button_text_en'  => $data['button_text_en'] ?? null,
            'button_text_ms'  => $data['button_text_ms'] ?? null,
            'button_url'      => $data['button_url'] ?? null,
            'sort_order'      => $data['sort_order'] ?? 0,
            'is_active'       => $request->boolean('is_active', true),
        ]);

        if ($request->hasFile('image')) {
            $this->deleteUpload($banner->image);   // remove old file
            $banner->image = $this->storeUpload($request->file('image'), 'banners');
        }
        if ($request->hasFile('mobile_image')) {
            $this->deleteUpload($banner->mobile_image);
            $banner->mobile_image = $this->storeUpload($request->file('mobile_image'), 'banners');
        }

        $banner->created_by = $banner->created_by ?? Auth::id();
        $banner->updated_by = Auth::id();
        $banner->save();

        return response()->json(['status' => 'success', 'message' => 'Banner saved', 'banner' => $banner]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}



    public function storeSectionItem(Request $request, WebsiteSection $section)
{
    $data = $request->validate([
        'item_id'         => 'nullable|exists:website_section_items,id',
        'icon'            => 'nullable|string|max:150',
        'value_en'        => 'nullable|string|max:100',
        'value_ms'        => 'nullable|string|max:100',
        'label_en'        => 'nullable|string|max:200',
        'label_ms'        => 'nullable|string|max:200',
        'description_en'  => 'nullable|string|max:255',
        'description_ms'  => 'nullable|string|max:255',
        'sort_order'      => 'nullable|integer',
        'image'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    try {
        $item = ! empty($data['item_id'])
            ? WebsiteSectionItem::findOrFail($data['item_id'])
            : new WebsiteSectionItem();

        $item->fill(array_merge($data, ['section_id' => $section->id, 'is_active' => true]));

        if ($request->hasFile('image')) {
            $item->image = $this->storeUpload($request->file('image'), 'website/section-items');
        }

        $item->save();

        return response()->json(['status' => 'success', 'message' => 'Item saved', 'item' => $item]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}

public function destroySectionItem(WebsiteSectionItem $item)
{
    $item->delete();
    return response()->json(['status' => 'success', 'message' => 'Item deleted']);
}

    public function toggleBanner(WebsiteBanner $banner)
    {
        $banner->is_active = ! $banner->is_active;
        $banner->save();
        return response()->json(['status' => 'success', 'message' => 'Banner status updated']);
    }

    public function reorderBanners(Request $request)
    {
        // body: { "order": [5, 2, 7, 1] }  <- banner ids in new order
        foreach ($request->input('order', []) as $i => $id) {
            WebsiteBanner::where('id', $id)->update(['sort_order' => $i]);
        }
        return response()->json(['status' => 'success', 'message' => 'Order updated']);
    }

public function destroyBanner(WebsiteBanner $banner)
{
    try {
        $this->deleteUpload($banner->image);
        $this->deleteUpload($banner->mobile_image);
        $banner->delete();
        return response()->json(['status' => 'success', 'message' => 'Banner deleted']);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}

    /* ───────────── Sections (stats / features) ───────────── */

    public function storeSection(Request $request, WebsitePage $page)
    {
        $data = $request->validate([
            'section_id'  => 'nullable|exists:website_sections,id',
            'section_key' => 'required|string|max:100',
            'icon'        => 'nullable|string|max:150',
            'label_en'    => 'nullable|string|max:200',
            'label_ms'    => 'nullable|string|max:200',
            'value_en'    => 'nullable|string|max:100',
            'value_ms'    => 'nullable|string|max:100',
            'sort_order'  => 'nullable|integer',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        try {
            $section = ! empty($data['section_id'])
                ? WebsiteSection::findOrFail($data['section_id'])
                : new WebsiteSection();

            $section->fill([
                'page_id'     => $page->id,
                'section_key' => $data['section_key'],
                'icon'        => $data['icon'] ?? null,
                'label_en'    => $data['label_en'] ?? null,
                'label_ms'    => $data['label_ms'] ?? null,
                'value_en'    => $data['value_en'] ?? null,
                'value_ms'    => $data['value_ms'] ?? null,
                'sort_order'  => $data['sort_order'] ?? 0,
                'is_active'   => true,
            ]);

            if ($request->hasFile('image')) {
                $section->image = $this->storeUpload($request->file('image'), 'website/sections');
            }

            $section->save();

            return response()->json(['status' => 'success', 'message' => 'Section saved', 'section' => $section]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function destroySection(WebsiteSection $section)
    {
        $section->delete();
        return response()->json(['status' => 'success', 'message' => 'Section deleted']);
    }

    /* ───────────── Global Settings ───────────── */

    public function settings()
    {
        $settings = WebsiteSetting::orderBy('group')->get()->groupBy('group');
        return view('website.settings', compact('settings'));
    }

    public function storeSettings(Request $request)
    {
        // body: { settings: { site_name: {value_en, value_ms}, footer_text: {...} } }
        $data = $request->validate([
            'settings' => 'required|array',
        ]);

        try {
            foreach ($data['settings'] as $key => $values) {
                WebsiteSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value_en' => $values['value_en'] ?? null,
                        'value_ms' => $values['value_ms'] ?? null,
                    ]
                );
            }
            return response()->json(['status' => 'success', 'message' => 'Settings saved']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /* ───────────── Helper (identical pattern to BookController) ───────────── */

    /* ── Streams website images from project-root uploads/website/{folder}/ (outside public/) ── */
public function serveImage(string $folder, string $filename)
{
    abort_unless(in_array($folder, self::ALLOWED_FOLDERS, true), 404);

    $path = base_path(self::UPLOAD_DIR . '/' . $folder . '/' . basename($filename));

    if (! File::exists($path) || ! File::isFile($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type'  => File::mimeType($path) ?: 'application/octet-stream',
        'Cache-Control' => 'public, max-age=86400',
    ]);
}

private function storeUpload($file, string $folder): string
{
    $uploadPath = base_path(self::UPLOAD_DIR . '/' . $folder);   // -> Pnbooks\uploads\website\banners

    if (! File::exists($uploadPath)) {
        File::makeDirectory($uploadPath, 0755, true);
    }

    $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
        . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

    $file->move($uploadPath, $filename);

    // stored in DB as "uploads/website/banners/xxx.jpg"
    return self::UPLOAD_DIR . '/' . $folder . '/' . $filename;
}

private function deleteUpload(?string $relativePath): void
{
    if ($relativePath && File::exists(base_path($relativePath))) {
        File::delete(base_path($relativePath));
    }
}
}