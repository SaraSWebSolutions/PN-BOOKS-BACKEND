<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookFormat;
use App\Models\Category;
use App\Models\Country;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::with(['book', 'category', 'format', 'country'])->latest()->get();

        $stats = [
            'total'    => Offer::count(),
            'active'   => Offer::active()->count(),
            'inactive' => Offer::where('status', false)->count(),
        ];

        $categories = Category::active()->ordered()->get(['id', 'name_en', 'name_ms']);
        $formats    = BookFormat::active()->ordered()->get(['id', 'name']);
        $countries  = Country::active()->ordered()->get(['id', 'name']);

        return view('offers.index', compact('offers', 'stats', 'categories', 'formats', 'countries'));
    }

    private function rules(): array
    {
        return [
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string|max:1000',
            'target_type'    => 'required|in:all,book,category,format',
            'book_id'        => 'required_if:target_type,book|nullable|exists:books,id',
            'category_id'    => 'required_if:target_type,category|nullable|exists:categories,id',
            'book_format_id' => 'required_if:target_type,format|nullable|exists:book_formats,id',
            'country_id'     => 'nullable|exists:countries,id',
            'discount_type'  => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'starts_at'      => 'nullable|date',
            'ends_at'        => 'nullable|date|after_or_equal:starts_at',
            'status'         => 'nullable|boolean',
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $data = $this->cleanTargetFields($data);

        // Find any already-active promotion with the SAME scope
        // (same target_type + target id + country_id) whose date range
        // overlaps this new one, and deactivate it before creating this one.
        $conflicts = $this->findConflictingOffers($data);

        if ($conflicts->isNotEmpty()) {
            Offer::whereIn('id', $conflicts->pluck('id'))->update(['status' => false]);
        }

        $offer = Offer::create($data);

        return response()->json([
            'status'  => 'success',
            'message' => $this->buildSaveMessage($offer, $conflicts, created: true),
            'offer'   => $offer,
        ]);
    }

    public function update(Request $request, Offer $offer): JsonResponse
    {
        $data = $request->validate($this->rules());
        $data = $this->cleanTargetFields($data);

        // Same overlap check, but exclude the promotion being edited itself
        $conflicts = $this->findConflictingOffers($data, excludeId: $offer->id);

        if ($conflicts->isNotEmpty()) {
            Offer::whereIn('id', $conflicts->pluck('id'))->update(['status' => false]);
        }

        $offer->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => $this->buildSaveMessage($offer->fresh(), $conflicts, created: false),
            'offer'   => $offer->fresh(),
        ]);
    }

    private function cleanTargetFields(array $data): array
    {
        $data['book_id']        = $data['target_type'] === 'book' ? $data['book_id'] : null;
        $data['category_id']    = $data['target_type'] === 'category' ? $data['category_id'] : null;
        $data['book_format_id'] = $data['target_type'] === 'format' ? $data['book_format_id'] : null;
        return $data;
    }

    
    private function findConflictingOffers(array $data, ?int $excludeId = null): Collection
    {
        $query = Offer::where('status', true)
            ->where('target_type', $data['target_type'])
            ->where('country_id', $data['country_id']); // exact match, including both-null (global)

        match ($data['target_type']) {
            'book'     => $query->where('book_id', $data['book_id']),
            'category' => $query->where('category_id', $data['category_id']),
            'format'   => $query->where('book_format_id', $data['book_format_id']),
            default    => null, // 'all' has no extra target id to match
        };

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $newStart = $data['starts_at'] ?? null;
        $newEnd   = $data['ends_at'] ?? null;

        if ($newStart) {
            $query->where(function ($q) use ($newStart) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $newStart);
            });
        }

        if ($newEnd) {
            $query->where(function ($q) use ($newEnd) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $newEnd);
            });
        }

        return $query->get();
    }

    private function buildSaveMessage(Offer $offer, Collection $conflicts, bool $created): string
    {
        $base = $created
            ? "Promotion '{$offer->title}' created! 🎉"
            : "Promotion '{$offer->title}' updated! ✅";

        if ($conflicts->isEmpty()) {
            return $base;
        }

        $names = $conflicts->pluck('title')->map(fn ($t) => "'{$t}'")->implode(', ');
        $plural = $conflicts->count() > 1 ? 'promotions' : 'promotion';

        return $base . " Note: existing {$plural} {$names} overlapped this date range and " .
            ($conflicts->count() > 1 ? 'were' : 'was') . ' automatically set to inactive.';
    }

    public function toggleStatus(Offer $offer): JsonResponse
    {
        $offer->update(['status' => ! $offer->status]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$offer->title}' " . ($offer->status ? 'activated' : 'deactivated') . '.',
            'is_active' => $offer->status,
        ]);
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $title = $offer->title;
        $offer->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$title}' deleted.",
        ]);
    }

    public function searchBooks(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $books = Book::query()
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->published()
            ->limit(20)
            ->get(['id', 'title', 'cover_image']);

        return response()->json($books->map(fn ($b) => [
            'id'    => $b->id,
            'title' => $b->title,
            'cover' => $b->cover_image_url,
        ]));
    }
}