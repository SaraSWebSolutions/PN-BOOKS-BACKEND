<?php

namespace App\Http\Controllers;

use App\Models\AuthorProfile;
use App\Models\Book;
use App\Models\Order;
use App\Models\PublisherProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard($request);
        }

        if ($user->hasRole('author')) {
            return $this->authorDashboard($user, $request);
        }

        if ($user->hasRole('publisher')) {
            return $this->publisherDashboard($user, $request);
        }

        // Fallback for any other/unknown role (e.g. customer landing on /dashboard)
        [$activeRange, $rangeStart, $rangeEnd] = $this->resolveDateRange($request);

        return view('dashboard', [
            'role'          => 'guest',
            'stats'         => [],
            'recentBooks'   => collect(),
            'recentOrders'  => collect(),
            'chart'         => ['labels' => [], 'values' => []],
            'statusChart'   => ['labels' => [], 'values' => []],
            'categoryChart' => ['labels' => [], 'values' => []],
            'activeRange'   => $activeRange,
            'rangeStart'    => $rangeStart,
            'rangeEnd'      => $rangeEnd,
            'orderStats'    => ['count' => 0, 'billed' => 0, 'received' => 0, 'pending' => 0],
        ]);
    }

    /* ───────────────────────── Admin ───────────────────────── */

    protected function adminDashboard(Request $request)
    {
        [$activeRange, $rangeStart, $rangeEnd] = $this->resolveDateRange($request);

        $stats = [
            'total_books'      => Book::count(),
            'published_books'  => Book::where('status', 'published')->count(),
            'draft_books'      => Book::where('status', 'draft')->count(),
            'featured_books'   => Book::where('is_featured', true)->count(),
            'total_authors'    => AuthorProfile::count(),
            'total_publishers' => PublisherProfile::count(),
            'total_customers'  => User::role('customer')->count(),
        ];

        $recentBooks = Book::with(['category', 'author.user', 'publisher.user'])
            ->latest()
            ->limit(10)
            ->get();

        $topAuthors = AuthorProfile::with('user')
            ->withCount('books')
            ->orderByDesc('books_count')
            ->limit(5)
            ->get();

        $topPublishers = PublisherProfile::with('user')
            ->withCount('books')
            ->orderByDesc('books_count')
            ->limit(5)
            ->get();

        $orderQuery = Order::whereBetween('placed_at', [$rangeStart, $rangeEnd]);

        $recentOrders = (clone $orderQuery)->with('user')
            ->latest('placed_at')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'role'          => 'admin',
            'stats'         => $stats,
            'recentBooks'   => $recentBooks,
            'recentOrders'  => $recentOrders,
            'topAuthors'    => $topAuthors,
            'topPublishers' => $topPublishers,
            'chart'         => $this->monthlyBookChart(Book::query()),
            'statusChart'   => $this->statusChart(Book::query()),
            'categoryChart' => $this->categoryChart(Book::query()),
            'activeRange'   => $activeRange,
            'rangeStart'    => $rangeStart,
            'rangeEnd'      => $rangeEnd,
            'orderStats'    => $this->orderStats($rangeStart, $rangeEnd),
            'ordersChart'         => $this->ordersChart($rangeStart, $rangeEnd),
            'paymentStatusChart'  => $this->paymentStatusChart($rangeStart, $rangeEnd),
        ]);
    }

    /* ───────────────────────── Author ───────────────────────── */

    protected function authorDashboard($user, Request $request)
    {
        [$activeRange, $rangeStart, $rangeEnd] = $this->resolveDateRange($request);

        $profile = AuthorProfile::where('user_id', $user->id)->first();

        $bookQuery = Book::where('author_id', optional($profile)->id ?? 0);

        $stats = [
            'total_books'     => (clone $bookQuery)->count(),
            'published_books' => (clone $bookQuery)->where('status', 'published')->count(),
            'draft_books'     => (clone $bookQuery)->where('status', 'draft')->count(),
            'featured_books'  => (clone $bookQuery)->where('is_featured', true)->count(),
        ];

        $recentBooks = (clone $bookQuery)->with(['category', 'publisher.user'])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard', [
            'role'          => 'author',
            'profile'       => $profile,
            'stats'         => $stats,
            'recentBooks'   => $recentBooks,
            'recentOrders'  => collect(),
            'chart'         => $this->monthlyBookChart(clone $bookQuery),
            'statusChart'   => $this->statusChart(clone $bookQuery),
            'categoryChart' => $this->categoryChart(clone $bookQuery),
            'activeRange'   => $activeRange,
            'rangeStart'    => $rangeStart,
            'rangeEnd'      => $rangeEnd,
            // Authors don't see store-wide order/revenue figures; kept at zero
            // so the shared date-filter bar and revenue panel still render.
            'orderStats'    => ['count' => 0, 'billed' => 0, 'received' => 0, 'pending' => 0],
        ]);
    }

    /* ───────────────────────── Publisher ───────────────────────── */

    protected function publisherDashboard($user, Request $request)
    {
        [$activeRange, $rangeStart, $rangeEnd] = $this->resolveDateRange($request);

        $profile = PublisherProfile::where('user_id', $user->id)->first();

        $bookQuery = Book::where('publisher_id', optional($profile)->id ?? 0);

        $stats = [
            'total_books'     => (clone $bookQuery)->count(),
            'published_books' => (clone $bookQuery)->where('status', 'published')->count(),
            'draft_books'     => (clone $bookQuery)->where('status', 'draft')->count(),
            'featured_books'  => (clone $bookQuery)->where('is_featured', true)->count(),
        ];

        $recentBooks = (clone $bookQuery)->with(['category', 'author.user'])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard', [
            'role'          => 'publisher',
            'profile'       => $profile,
            'stats'         => $stats,
            'recentBooks'   => $recentBooks,
            'recentOrders'  => collect(),
            'chart'         => $this->monthlyBookChart(clone $bookQuery),
            'statusChart'   => $this->statusChart(clone $bookQuery),
            'categoryChart' => $this->categoryChart(clone $bookQuery),
            'activeRange'   => $activeRange,
            'rangeStart'    => $rangeStart,
            'rangeEnd'      => $rangeEnd,
            'orderStats'    => ['count' => 0, 'billed' => 0, 'received' => 0, 'pending' => 0],
        ]);
    }

    /* ───────────────────────── Date range helper ───────────────────────── */

    /**
     * Resolve the active range key + start/end Carbon instances from the
     * request's `range` (and optional `from`/`to` for custom) query params.
     */
    protected function resolveDateRange(Request $request): array
    {
        $range = $request->get('range', 'this_month');
        $now = Carbon::now();

        switch ($range) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                break;
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                break;
            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end   = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end   = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                break;
            case 'last_year':
                $start = $now->copy()->subYear()->startOfYear();
                $end   = $now->copy()->subYear()->endOfYear();
                break;
            case 'custom':
                $start = $request->filled('from')
                    ? Carbon::parse($request->from)->startOfDay()
                    : $now->copy()->startOfMonth();
                $end = $request->filled('to')
                    ? Carbon::parse($request->to)->endOfDay()
                    : $now->copy()->endOfMonth();
                break;
            case 'this_month':
            default:
                $range = 'this_month';
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                break;
        }

        return [$range, $start, $end];
    }

    /* ───────────────────────── Order/revenue helpers ───────────────────────── */

    /**
     * Order count / billed / received / pending totals for the given range.
     * Adjust the 'paid' string below if your payment_status enum differs.
     */
    protected function orderStats($start, $end): array
    {
        $query = Order::whereBetween('placed_at', [$start, $end]);

        $billed   = (float) (clone $query)->sum('total_amount');
        $received = (float) (clone $query)->where('payment_status', 'paid')->sum('total_amount');
        $count    = (clone $query)->count();

        return [
            'count'    => $count,
            'billed'   => $billed,
            'received' => $received,
            'pending'  => max($billed - $received, 0),
        ];
    }

    /**
     * Daily order count + revenue series across the selected range, capped
     * at 31 points so very wide ranges (e.g. "This Year") still render
     * cleanly — in that case it buckets by month instead of by day.
     */
    protected function ordersChart($start, $end): array
    {
        $labels  = [];
        $counts  = [];
        $revenue = [];

        $daysInRange = $start->diffInDays($end);

        if ($daysInRange > 62) {
            // Bucket by month for long ranges (This Year / Last Year / wide custom).
            $cursor = $start->copy()->startOfMonth();
            $endMonth = $end->copy()->startOfMonth();

            while ($cursor->lte($endMonth)) {
                $monthStart = $cursor->copy()->startOfMonth();
                $monthEnd   = $cursor->copy()->endOfMonth();

                $labels[]  = $cursor->format('M Y');
                $counts[]  = Order::whereBetween('placed_at', [$monthStart, $monthEnd])->count();
                $revenue[] = (float) Order::whereBetween('placed_at', [$monthStart, $monthEnd])->sum('total_amount');

                $cursor->addMonth();
            }
        } else {
            // Bucket by day for short/medium ranges.
            $cursor = $start->copy()->startOfDay();

            while ($cursor->lte($end)) {
                $dayStart = $cursor->copy()->startOfDay();
                $dayEnd   = $cursor->copy()->endOfDay();

                $labels[]  = $cursor->format('d M');
                $counts[]  = Order::whereBetween('placed_at', [$dayStart, $dayEnd])->count();
                $revenue[] = (float) Order::whereBetween('placed_at', [$dayStart, $dayEnd])->sum('total_amount');

                $cursor->addDay();
            }
        }

        return ['labels' => $labels, 'counts' => $counts, 'revenue' => $revenue];
    }

    /**
     * Payment status breakdown (paid / unpaid / etc.) for the doughnut chart.
     */
    protected function paymentStatusChart($start, $end): array
    {
        $rows = Order::whereBetween('placed_at', [$start, $end])
            ->selectRaw('payment_status, COUNT(*) as total')
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status');

        return [
            'labels' => $rows->keys()->map(fn ($s) => ucfirst($s))->values()->all(),
            'values' => $rows->values()->all(),
        ];
    }

    /* ───────────────────────── Book chart helpers ───────────────────────── */

    /**
     * Books added per month for the last 6 months, scoped by whatever
     * query builder is passed in (already filtered by author/publisher/admin).
     */
    protected function monthlyBookChart($query): array
    {
        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $values[] = (clone $query)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Draft vs Published (vs any other status) breakdown.
     */
    protected function statusChart($query): array
    {
        $rows = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => $rows->keys()->map(fn ($s) => ucfirst($s))->values()->all(),
            'values' => $rows->values()->all(),
        ];
    }

    /**
     * Books grouped by category (top 6, rest grouped as "Other").
     */
    protected function categoryChart($query): array
    {
        $rows = (clone $query)
            ->with('category')
            ->get()
            ->groupBy(fn ($book) => optional($book->category)->name_en ?? 'Uncategorized')
            ->map->count()
            ->sortDesc();

        $top   = $rows->take(6);
        $other = $rows->slice(6)->sum();

        if ($other > 0) {
            $top->put('Other', $other);
        }

        return ['labels' => $top->keys()->all(), 'values' => $top->values()->all()];
    }
}