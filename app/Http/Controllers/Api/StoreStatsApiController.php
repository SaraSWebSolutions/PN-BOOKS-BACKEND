<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\BookReview;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StoreStatsApiController extends Controller
{
    protected array $deliveredStatuses = ['shipped', 'completed'];

    public function glance(Request $request)
    {
        $end   = $request->filled('end')   ? Carbon::parse($request->end)->endOfDay() : now();
        $start = $request->filled('start') ? Carbon::parse($request->start)->startOfDay() : $end->copy()->subMonths(12);

        $periodDays = $start->diffInDays($end);
        $prevEnd    = $start->copy()->subSecond();
        $prevStart  = $prevEnd->copy()->subDays($periodDays);

        $current  = $this->computeStats($start, $end);
        $previous = $this->computeStats($prevStart, $prevEnd);

        return response()->json([
            'period' => [
                'current'  => [$start->toDateString(), $end->toDateString()],
                'previous' => [$prevStart->toDateString(), $prevEnd->toDateString()],
            ],
            'total_books_sold' => $this->withChange($current['books_sold'], $previous['books_sold']),
            'happy_customers'  => $this->withChange($current['customers'], $previous['customers']),
            'orders_delivered' => $this->withChange($current['delivered_pct'], $previous['delivered_pct']),
            'average_rating'   => $this->withChange($current['avg_rating'], $previous['avg_rating']),
        ]);
    }

    protected function computeStats(Carbon $start, Carbon $end): array
    {
        $ordersQuery = Order::whereBetween('created_at', [$start, $end]);

        $totalOrders    = (clone $ordersQuery)->count();
        $deliveredCount = (clone $ordersQuery)->whereIn('status', $this->deliveredStatuses)->count();
        $deliveredPct   = $totalOrders > 0 ? round(($deliveredCount / $totalOrders) * 100, 1) : 0;

        $booksSold = (int) OrderItem::whereHas('order', function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                  ->whereIn('status', $this->deliveredStatuses);
            })->sum('quantity');

        $customers = (clone $ordersQuery)->distinct('user_id')->count('user_id');

      $avgRating = (float) BookReview::whereBetween('created_at', [$start, $end])->avg('rating');
$avgRating = round($avgRating ?? 0, 1);

return [
    'books_sold'    => $booksSold,
    'customers'     => $customers,
    'delivered_pct' => $deliveredPct,
    'avg_rating'    => $avgRating,
];
    }

    protected function withChange($current, $previous): array
    {
        if ($previous == 0) {
            $change = $current > 0 ? 100.0 : 0.0;
        } else {
            $change = round((($current - $previous) / $previous) * 100, 1);
        }

        return [
            'value'          => $current,
            'previous_value' => $previous,
            'change_percent' => $change,
            'trend'          => $change >= 0 ? 'up' : 'down',
        ];
    }
}