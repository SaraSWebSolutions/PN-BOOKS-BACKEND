@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
{{-- <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"> --}}
<style>
:root{
    --brand:#6366f1;--brand-dark:#4338ca;--brand-light:#eef2ff;
    --green:#10b981;--orange:#f97316;--red:#ef4444;--gold:#f59e0b;--blue:#3b82f6;--purple:#8b5cf6;
    --bg:#f8fafc;--card:#fff;--text:#0f172a;--muted:#64748b;--border:#eef1f5;--r:14px;
}
/* *{font-family:'Outfit',sans-serif;} */
.main-content{background:var(--bg);}

/* ══ page wrapper — gap fixed: full width, no dead space on sides ══ */
.dash-wrap{padding:22px 20px 40px;max-width:100%;margin:0;}

.dash-hello{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;}
.dash-hello h4{font-weight:800;color:var(--text);margin:0;font-size:20px;}
.dash-hello p{margin:3px 0 0;color:var(--muted);font-size:13px;}
.role-pill{font-size:11px;font-weight:700;padding:6px 14px;border-radius:20px;background:var(--brand-light);color:var(--brand);text-transform:capitalize;}

/* ══ date filter bar ══ */
.date-filter-bar{background:var(--card);border-radius:16px;border:1px solid var(--border);padding:16px 20px;margin-bottom:20px;}
.pill-btn{border:1px solid var(--border);background:#fff;color:var(--text);font-size:12.5px;font-weight:600;
    padding:8px 16px;border-radius:20px;cursor:pointer;transition:.15s;white-space:nowrap;}
.pill-btn:hover{background:var(--brand-light);}
.pill-btn.active{background:var(--brand);border-color:var(--brand);color:#fff;}
.custom-range-box{display:flex;align-items:center;gap:8px;margin-left:4px;flex-wrap:wrap;}
.custom-range-box input[type=date]{border:1px solid var(--border);border-radius:10px;padding:6px 10px;font-size:12px;}
.range-summary{margin-top:12px;font-size:12.5px;color:var(--muted);display:flex;align-items:center;gap:6px;flex-wrap:wrap;}

/* ══ revenue panel (dark, order-based) ══ */
.revenue-panel{background:linear-gradient(135deg,#1e1b3a,#251f47);border-radius:16px;padding:22px 24px;margin-bottom:20px;color:#fff;}
.rev-head{display:flex;justify-content:space-between;align-items:center;font-size:11px;font-weight:700;letter-spacing:.5px;color:#c7c3ea;margin-bottom:18px;flex-wrap:wrap;gap:8px;}
.rev-head i{margin-right:4px;}
.rev-badge{background:rgba(255,255,255,.1);padding:4px 10px;border-radius:20px;}
.rev-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:700px){.rev-grid{grid-template-columns:1fr;}}
.rev-lbl{font-size:10.5px;color:#a29dd6;letter-spacing:.5px;margin-bottom:6px;}
.rev-val{font-size:24px;font-weight:800;}
.rev-green{color:#34d399;}
.rev-orange{color:#fb923c;}
.rev-sub{font-size:11px;color:#a29dd6;margin-top:4px;}

/* ══ flat KPI cards ══ */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px;}
@media(max-width:1100px){.kpi-row{grid-template-columns:repeat(2,1fr);}}
@media(max-width:560px){.kpi-row{grid-template-columns:1fr;}}
.kpi-card{background:var(--card);border-radius:16px;padding:20px 22px;border:1px solid var(--border);}
.kpi-top{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
.kpi-ico{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;}
.kpi-lbl{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.kpi-val{font-size:26px;font-weight:800;color:var(--text);line-height:1;}
.kpi-sub{font-size:11.5px;color:var(--muted);margin-top:6px;}
.ki-brand{background:var(--brand-light);color:var(--brand);}
.ki-green{background:#ecfdf5;color:#059669;}
.ki-orange{background:#fff7ed;color:#c2410c;}
.ki-gold{background:#fffbeb;color:#d97706;}
.ki-blue{background:#eff6ff;color:#2563eb;}
.ki-purple{background:#f5f3ff;color:#7c3aed;}
.ki-red{background:#fef2f2;color:#dc2626;}

/* ══ panels ══ */
.two-col{display:grid;grid-template-columns:1.3fr 1fr;gap:18px;margin-bottom:20px;align-items:stretch;}
@media(max-width:1000px){.two-col{grid-template-columns:1fr;}}
.panel{background:var(--card);border-radius:16px;border:1px solid var(--border);overflow:hidden;display:flex;flex-direction:column;}
.panel-head{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px;}
.panel-title{display:flex;align-items:center;gap:9px;font-size:13.5px;font-weight:700;color:var(--text);}
.pico{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;background:var(--brand-light);color:var(--brand);}
.panel-body{padding:16px 20px;flex:1;}

/* ══ FIXED, SMALL chart heights ══ */
.chart-box{position:relative;width:100%;height:220px;}
.chart-box.chart-sm{height:190px;}

.rt td,.rt th{padding:10px 14px;font-size:12.5px;vertical-align:middle;border-color:var(--border);}
.rt thead th{background:var(--bg);font-weight:700;font-size:9.5px;letter-spacing:.6px;text-transform:uppercase;color:var(--muted);}
.sbadge{display:inline-flex;align-items:center;gap:3px;padding:3px 9px;border-radius:20px;font-size:10.5px;font-weight:600;text-transform:capitalize;}
.sb-draft{background:#fff7ed;color:#c2410c;}
.sb-published{background:#d1fae5;color:#065f46;}
.sb-pending{background:#fff7ed;color:#c2410c;}
.sb-processing{background:#eff6ff;color:#2563eb;}
.sb-completed{background:#d1fae5;color:#065f46;}
.sb-cancelled{background:#fef2f2;color:#dc2626;}
.sb-paid{background:#d1fae5;color:#065f46;}
.sb-unpaid{background:#fef2f2;color:#dc2626;}
.book-thumb{width:30px;height:40px;border-radius:5px;object-fit:cover;border:1px solid var(--border);background:var(--bg);}
.book-thumb-placeholder{width:30px;height:40px;border-radius:5px;border:1px solid var(--border);background:var(--bg);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;}
.book-thumb-placeholder i{font-size:12px;color:var(--muted);}
.empty-state{text-align:center;padding:26px 18px;color:var(--muted);font-size:12.5px;}

.rank-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f4f6f8;}
.rank-row:last-child{border-bottom:none;}
.rank-badge{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10.5px;font-weight:800;background:var(--bg);color:var(--muted);flex-shrink:0;}
.rank-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0;color:#fff;background:var(--brand);}
.rank-name{font-size:12.5px;font-weight:700;color:var(--text);}
.rank-sub{font-size:10px;color:var(--muted);}
.rank-count{margin-left:auto;font-size:12.5px;font-weight:800;color:var(--text);}
</style>
@endpush

@section('content')
<div class="dash-wrap">

    <div class="dash-hello">
        <div>
            <h4>Welcome back, {{ auth()->user()->name ?? 'there' }} 👋</h4>
            <p>Here's what's happening with {{ $role === 'admin' ? 'the store' : 'your books' }} today.</p>
        </div>
        <span class="role-pill"><i class="feather-user"></i> {{ $role }}</span>
    </div>

    {{-- ══════════ DATE FILTER BAR ══════════ --}}
    <div class="date-filter-bar">
        <form id="dateFilterForm" method="GET" class="d-flex flex-wrap align-items-center gap-2">
            @php
                $ranges = [
                    'today'      => 'Today',
                    'yesterday'  => 'Yesterday',
                    'this_week'  => 'This Week',
                    'last_week'  => 'Last Week',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year'  => 'This Year',
                    'last_year'  => 'Last Year',
                ];
            @endphp

            @foreach($ranges as $key => $label)
                <button type="submit" name="range" value="{{ $key }}"
                    class="pill-btn {{ $activeRange === $key ? 'active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach

            <button type="button" class="pill-btn {{ $activeRange === 'custom' ? 'active' : '' }}"
                onclick="document.getElementById('customRangeBox').classList.toggle('d-none')">
                <i class="feather-calendar"></i> Custom
            </button>

            <div id="customRangeBox" class="custom-range-box {{ $activeRange === 'custom' ? '' : 'd-none' }}">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="from" value="{{ request('from', $rangeStart->format('Y-m-d')) }}">
                <span>to</span>
                <input type="date" name="to" value="{{ request('to', $rangeEnd->format('Y-m-d')) }}">
                <button type="submit" class="pill-btn active">Apply</button>
            </div>
        </form>

        <div class="range-summary">
            <i class="feather-calendar"></i>
            {{ $rangeStart->format('d M Y') }} → {{ $rangeEnd->format('d M Y') }}
            &nbsp;·&nbsp; {{ $orderStats['count'] }} orders
            &nbsp;·&nbsp; Billed ₹{{ number_format($orderStats['billed'], 0) }}
            &nbsp;·&nbsp; Received ₹{{ number_format($orderStats['received'], 0) }}
        </div>
    </div>

    {{-- ══════════ REVENUE SUMMARY (order based) ══════════ --}}
    <div class="revenue-panel">
        <div class="rev-head">
            <span><i class="feather-trending-up"></i> REVENUE SUMMARY — {{ $rangeStart->format('d M') }} → {{ $rangeEnd->format('d M Y') }}</span>
            <span class="rev-badge">{{ $orderStats['count'] }} ORDERS</span>
        </div>
        <div class="rev-grid">
            <div>
                <div class="rev-lbl">TOTAL BILLED</div>
                <div class="rev-val">₹{{ number_format($orderStats['billed'], 0) }}</div>
                <div class="rev-sub">{{ $orderStats['count'] }} orders</div>
            </div>
            <div>
                <div class="rev-lbl">RECEIVED</div>
                <div class="rev-val rev-green">₹{{ number_format($orderStats['received'], 0) }}</div>
                <div class="rev-sub">Paid orders</div>
            </div>
            <div>
                <div class="rev-lbl">PENDING</div>
                <div class="rev-val rev-orange">₹{{ number_format($orderStats['pending'], 0) }}</div>
                <div class="rev-sub">Awaiting payment</div>
            </div>
        </div>
    </div>

    {{-- ══════════ KPI ROW 1 (books) ══════════ --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-brand"><i class="feather-book"></i></div>
                <div class="kpi-lbl">Total Books</div>
            </div>
            <div class="kpi-val">{{ $stats['total_books'] ?? 0 }}</div>
            <div class="kpi-sub">All books</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-green"><i class="feather-check-circle"></i></div>
                <div class="kpi-lbl">Published</div>
            </div>
            <div class="kpi-val">{{ $stats['published_books'] ?? 0 }}</div>
            <div class="kpi-sub">Live in store</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-orange"><i class="feather-edit-3"></i></div>
                <div class="kpi-lbl">Drafts</div>
            </div>
            <div class="kpi-val">{{ $stats['draft_books'] ?? 0 }}</div>
            <div class="kpi-sub">Not published yet</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-gold"><i class="feather-star"></i></div>
                <div class="kpi-lbl">Featured</div>
            </div>
            <div class="kpi-val">{{ $stats['featured_books'] ?? 0 }}</div>
            <div class="kpi-sub">Highlighted books</div>
        </div>
    </div>

    @if($role === 'admin')
    {{-- ══════════ KPI ROW 2 — orders (admin only) ══════════ --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-purple"><i class="feather-shopping-cart"></i></div>
                <div class="kpi-lbl">Orders</div>
            </div>
            <div class="kpi-val">{{ $orderStats['count'] }}</div>
            <div class="kpi-sub">In selected range</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-green"><i class="feather-dollar-sign"></i></div>
                <div class="kpi-lbl">Received</div>
            </div>
            <div class="kpi-val">₹{{ number_format($orderStats['received'], 0) }}</div>
            <div class="kpi-sub">Paid orders</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-red"><i class="feather-clock"></i></div>
                <div class="kpi-lbl">Pending</div>
            </div>
            <div class="kpi-val">₹{{ number_format($orderStats['pending'], 0) }}</div>
            <div class="kpi-sub">Awaiting payment</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-brand"><i class="feather-shopping-bag"></i></div>
                <div class="kpi-lbl">Customers</div>
            </div>
            <div class="kpi-val">{{ $stats['total_customers'] ?? 0 }}</div>
            <div class="kpi-sub">Registered customers</div>
        </div>
    </div>

    {{-- ══════════ KPI ROW 3 (admin only) ══════════ --}}
    <div class="kpi-row" style="grid-template-columns:repeat(2,1fr);">
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-blue"><i class="feather-users"></i></div>
                <div class="kpi-lbl">Authors</div>
            </div>
            <div class="kpi-val">{{ $stats['total_authors'] ?? 0 }}</div>
            <div class="kpi-sub">Registered authors</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top">
                <div class="kpi-ico ki-purple"><i class="feather-briefcase"></i></div>
                <div class="kpi-lbl">Publishers</div>
            </div>
            <div class="kpi-val">{{ $stats['total_publishers'] ?? 0 }}</div>
            <div class="kpi-sub">Active publishers</div>
        </div>
    </div>
    @endif

    {{-- ══════════ CHARTS: books + orders ══════════ --}}
    <div class="two-col">
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-trending-up"></i></span> Books Added — Last 6 Months</div>
            </div>
            <div class="panel-body">
                <div class="chart-box">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-pie-chart"></i></span> Status Breakdown</div>
            </div>
            <div class="panel-body" style="display:flex;align-items:center;justify-content:center;">
                <div class="chart-box chart-sm" style="max-width:220px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if($role === 'admin')
    <div class="two-col">
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-bar-chart-2"></i></span> Orders &amp; Revenue — Selected Range</div>
            </div>
            <div class="panel-body">
                <div class="chart-box">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-credit-card"></i></span> Payment Status</div>
            </div>
            <div class="panel-body" style="display:flex;align-items:center;justify-content:center;">
                <div class="chart-box chart-sm" style="max-width:220px;">
                    <canvas id="paymentStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="two-col">
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-book-open"></i></span> Recent Books</div>
            </div>
            <div class="panel-body" style="padding:0;">
                @if($recentBooks->count())
                    <table class="rt table mb-0">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Category</th>
                                @if($role === 'admin')
                                    <th>Author</th>
                                    <th>Publisher</th>
                                @elseif($role === 'author')
                                    <th>Publisher</th>
                                @elseif($role === 'publisher')
                                    <th>Author</th>
                                @endif
                                <th>Status</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBooks as $book)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($book->cover_image_url)
                                            <img src="{{ $book->cover_image_url }}" class="book-thumb" alt="">
                                        @else
                                            <div class="book-thumb-placeholder" title="No cover uploaded">
                                                <i class="feather-image"></i>
                                            </div>
                                        @endif
                                        <span class="fw-semibold">{{ $book->title }}</span>
                                    </div>
                                </td>
                                <td>{{ $book->category->name_en ?? '—' }}</td>
                                @if($role === 'admin')
                                    <td>{{ optional($book->author->user ?? null)->name ?? '—' }}</td>
                                    <td>{{ optional($book->publisher->user ?? null)->name ?? '—' }}</td>
                                @elseif($role === 'author')
                                    <td>{{ optional($book->publisher->user ?? null)->name ?? '—' }}</td>
                                @elseif($role === 'publisher')
                                    <td>{{ optional($book->author->user ?? null)->name ?? '—' }}</td>
                                @endif
                                <td><span class="sbadge sb-{{ $book->status }}">{{ $book->status }}</span></td>
                                <td>{{ $book->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="feather-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        No books yet.
                    </div>
                @endif
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-grid"></i></span> By Category</div>
            </div>
            <div class="panel-body">
                <div class="chart-box chart-sm">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if($role === 'admin')
    {{-- ══════════ RECENT ORDERS ══════════ --}}
    <div class="two-col" style="grid-template-columns:1fr;">
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-shopping-cart"></i></span> Recent Orders</div>
            </div>
            <div class="panel-body" style="padding:0;">
                @if($recentOrders->count())
                    <table class="rt table mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>{{ optional($order->user)->name ?? 'Guest' }}</td>
                                <td><span class="sbadge sb-{{ $order->payment_status }}">{{ $order->payment_status }}</span></td>
                                <td><span class="sbadge sb-{{ $order->status }}">{{ $order->status }}</span></td>
                                <td>₹{{ number_format($order->total_amount, 2) }}</td>
                                <td>{{ optional($order->placed_at)->format('d M Y') ?? $order->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="feather-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        No orders in this range.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ TOP AUTHORS / PUBLISHERS ══════════ --}}
    <div class="two-col">
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-award"></i></span> Top Authors</div>
            </div>
            <div class="panel-body">
                @forelse($topAuthors as $i => $author)
                    <div class="rank-row">
                        <div class="rank-badge">{{ $i + 1 }}</div>
                        <div class="rank-avatar">{{ strtoupper(substr(optional($author->user)->name ?? 'A', 0, 1)) }}</div>
                        <div>
                            <div class="rank-name">{{ optional($author->user)->name ?? 'Unknown' }}</div>
                            <div class="rank-sub">Author</div>
                        </div>
                        <div class="rank-count">{{ $author->books_count ?? 0 }} books</div>
                    </div>
                @empty
                    <div class="empty-state">No authors yet.</div>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><span class="pico"><i class="feather-award"></i></span> Top Publishers</div>
            </div>
            <div class="panel-body">
                @forelse($topPublishers as $i => $publisher)
                    <div class="rank-row">
                        <div class="rank-badge">{{ $i + 1 }}</div>
                        <div class="rank-avatar">{{ strtoupper(substr($publisher->company_name ?? 'P', 0, 1)) }}</div>
                        <div>
                            <div class="rank-name">{{ $publisher->company_name ?? 'Unknown' }}</div>
                            <div class="rank-sub">Publisher</div>
                        </div>
                        <div class="rank-count">{{ $publisher->books_count ?? 0 }} books</div>
                    </div>
                @empty
                    <div class="empty-state">No publishers yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const brand = '#6366f1', green = '#10b981', orange = '#f97316', gold = '#f59e0b', blue = '#3b82f6', purple = '#8b5cf6', red = '#ef4444';
    const palette = [brand, green, orange, gold, blue, purple, red];

    // Monthly line chart — books
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: @json($chart['labels']),
            datasets: [{
                label: 'Books added',
                data: @json($chart['values']),
                borderColor: brand,
                backgroundColor: 'rgba(99,102,241,.10)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: brand,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // Book status doughnut
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: @json($statusChart['labels']),
            datasets: [{
                data: @json($statusChart['values']),
                backgroundColor: palette,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 10 } } } },
            cutout: '68%'
        }
    });

    // Category bar
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: @json($categoryChart['labels']),
            datasets: [{
                label: 'Books',
                data: @json($categoryChart['values']),
                backgroundColor: brand,
                borderRadius: 6,
                maxBarThickness: 26,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });

    @if($role === 'admin')
    // Orders + revenue combo chart (bar = orders count, line = revenue)
    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: @json($ordersChart['labels']),
            datasets: [
                {
                    type: 'bar',
                    label: 'Orders',
                    data: @json($ordersChart['counts']),
                    backgroundColor: 'rgba(99,102,241,.75)',
                    borderRadius: 6,
                    maxBarThickness: 26,
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'Revenue (₹)',
                    data: @json($ordersChart['revenue']),
                    borderColor: green,
                    backgroundColor: 'rgba(16,185,129,.10)',
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: green,
                    borderWidth: 2,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 10 } } } },
            scales: {
                y:  { beginAtZero: true, position: 'left',  ticks: { precision: 0, font: { size: 10 } } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 10 } } },
                x:  { ticks: { font: { size: 10 } } }
            }
        }
    });

    // Payment status doughnut
    new Chart(document.getElementById('paymentStatusChart'), {
        type: 'doughnut',
        data: {
            labels: @json($paymentStatusChart['labels']),
            datasets: [{
                data: @json($paymentStatusChart['values']),
                backgroundColor: [green, orange, red, blue, purple],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 10 } } } },
            cutout: '68%'
        }
    });
    @endif
});
</script>
@endpush