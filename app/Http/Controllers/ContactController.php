<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\BusinessLocation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // ─── Validation rules (shared) ────────────────────────────────────
    private function rules(string $ignoreId = ''): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'code'                 => 'nullable|string|unique:contacts,code' . ($ignoreId ? ",$ignoreId" : ''),
            'type'                 => 'required|in:customer,lead,both,supplier',
            'email'                => 'nullable|email|unique:contacts,email' . ($ignoreId ? ",$ignoreId" : ''),
             // ── Phone: unique + max 10 digits ─────────────────────────
            'phone'    => [
                'nullable',
                'string',
                'max:10',
                'min:10',
                'regex:/^[0-9]{10}$/',          // exactly 10 numeric digits
                'unique:contacts,phone' . ($ignoreId ? ",$ignoreId" : ''),
            ],
 
            // ── WhatsApp: same format check (not unique) ──────────────
            'whatsapp' => [
                'nullable',
                'string',
                'max:10',
                'min:10',
                'regex:/^[0-9]{10}$/',
            ],
            'aadhaar_number' => [
    'nullable', 'string',
    'regex:/^[0-9]{12}$/',
    'unique:contacts,aadhaar_number' . ($ignoreId ? ",$ignoreId" : ''),
],
'pan_number' => [
    'nullable', 'string',
    'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
    'unique:contacts,pan_number' . ($ignoreId ? ",$ignoreId" : ''),
],
            'date_of_birth'        => 'nullable|date',
            'gender'               => 'nullable|in:male,female,other',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:100',
            'state'                => 'nullable|string|max:100',
            'country'              => 'nullable|string|max:100',
            'pincode'              => 'nullable|string|max:20',
            'gstin'                => 'nullable|string|max:20',
            'credit_limit'         => 'nullable|numeric|min:0',
            'opening_balance'      => 'nullable|numeric',
            'notes'                => 'nullable|string',
            'status'               => 'required|in:active,inactive',
            'business_location_id' => 'nullable|exists:business_locations,id',
        ];
    }

    private function fields(): array
    {
        return [
            'name', 'code', 'type', 'email', 'phone', 'whatsapp',
            'date_of_birth', 'gender', 'address', 'city', 'state',
            'country', 'pincode', 'gstin', 'credit_limit',
            'opening_balance', 'notes', 'status', 'business_location_id','aadhaar_number', 'pan_number',
        ];
    }

    // ─── Index ────────────────────────────────────────────────────────
    public function index()
    {
        $contacts = Contact::with(['businessLocation'])->latest()->get();
        return view('contacts.index', compact('contacts'));
    }

    // ─── Customers only (filtered index) ─────────────────────────────
    public function customers()
    {
        $contacts = Contact::with(['businessLocation'])
            ->whereIn('type', ['customer', 'both'])
            ->latest()->get();
        return view('contacts.index', compact('contacts'));
    }

    // ─── Suppliers only ───────────────────────────────────────────────
    public function suppliers()
    {
        $contacts = Contact::with(['businessLocation'])
            ->where('type', 'supplier')
            ->latest()->get();
        return view('contacts.index', compact('contacts'));
    }

    // ─── Customer List (for ledger index) ────────────────────────────
    public function customerIndex(Request $request)
    {
        $query = Contact::whereIn('type', ['customer', 'both']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->withCount(['salesTransactions as total_bills'])
            ->withSum('salesTransactions as total_purchase', 'total_amount')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('contacts.customer-index', compact('customers'));
    }

    // ─── Create ───────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $locations   = BusinessLocation::where('status', 'active')->get();
        $code        = Contact::generateCode();
        $defaultType = $request->query('type', 'customer');
        return view('contacts.create', compact('locations', 'code', 'defaultType'));
    }

    // ─── Store ────────────────────────────────────────────────────────
  public function store(Request $request)
{
    $request->validate($this->rules());

    $contact = Contact::create([
        ...$request->only($this->fields()),
        'created_by' => auth()->id(),
    ]);

     // ── Activity Log ──────────────────────────────────────────────────
    ActivityLog::log(
        'contact',
        'created',
        $contact->id,
        $contact->name,
        [],
        $contact->only($this->fields())
    );

    if ($request->expectsJson()) {
        return response()->json([
            'status'  => 'success',
            'message' => 'Contact created successfully! 🎉',
            'contact' => [
                'id'    => $contact->id,
                'name'  => $contact->name,
                'phone' => $contact->phone,
            ],
        ]);
    }

    return response()->json([
        'status'   => 'success',
        'message'  => 'Contact created successfully! 🎉',
        'redirect' => route('contacts.index'),
    ]);
}

 public function show(Contact $contact)
{
    $contact->load(['businessLocation', 'createdBy']);

    // Load all credit bills for this customer
    $credits = \App\Models\CustomerCredit::with(['sale', 'payments'])
        ->where('contact_id', $contact->id)
        ->orderByDesc('created_at')
        ->get();

    $totalOutstanding = $credits->whereIn('status', ['open','partial','overdue'])->sum('balance_amount');
    $totalOverdue     = $credits->where('status', 'overdue')->sum('balance_amount');
    $overdueCount     = $credits->where('status', 'overdue')->count();

    return view('contacts.view', compact(
        'contact', 'credits', 'totalOutstanding', 'totalOverdue', 'overdueCount'
    ));
}
    // ─── Edit ─────────────────────────────────────────────────────────
    public function edit(Contact $contact)
    {
        $locations = BusinessLocation::where('status', 'active')->get();
        return view('contacts.edit', compact('contact', 'locations'));
    }

    // ─── Update ───────────────────────────────────────────────────────
   public function update(Request $request, Contact $contact)
{
    $request->validate($this->rules($contact->id));

    // ── Capture before ────────────────────────────────────────────────
    $oldValues = $contact->only($this->fields());

    $contact->update($request->only($this->fields()));

    // ── Capture after & log only changed fields ───────────────────────
    $newValues = $contact->fresh()->only($this->fields());

    $changed    = array_keys(array_diff_assoc($newValues, $oldValues));
    $oldChanged = array_intersect_key($oldValues, array_flip($changed));
    $newChanged = array_intersect_key($newValues, array_flip($changed));

    ActivityLog::log(
        'contact',
        'edited',
        $contact->id,
        $contact->name,
        $oldChanged,
        $newChanged
    );

    return response()->json([
        'status'   => 'success',
        'message'  => 'Contact updated successfully! ✅',
        'redirect' => route('contacts.index'),
    ]);
}

// In ContactController
public function json(Contact $contact)
{
    return response()->json([
        'status'  => 'success',
        'contact' => $contact->only([
            'id','name','phone','whatsapp','email',
            'city','state','address','type','status'
        ])
    ]);
}

// SaleController
public function linkCustomer(Request $request, $saleId)
{
    $request->validate(['contact_id' => 'required|exists:contacts,id']);

    \App\Models\SalesTransaction::where('id', $saleId)
        ->update(['contact_id' => $request->contact_id]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Customer linked successfully.'
    ]);
}

    // ─── Destroy ──────────────────────────────────────────────────────
    public function destroy(Contact $contact)
    {
           ActivityLog::log(
        'contact',
        'deleted',
        $contact->id,
        $contact->name,
        $contact->only($this->fields()),
        []
    );
        $contact->delete();
        return response()->json([
            'status'  => 'success',
            'message' => 'Contact deleted successfully!',
        ]);
    }

    // ─── Customer Ledger ──────────────────────────────────────────────
    public function customerLedger(Request $request, Contact $contact)
    {
        abort_if(!in_array($contact->type, ['customer', 'both']), 404);

        // ── Date range ────────────────────────────────────────────
        $fromDate = $request->get('from_date')
            ? \Carbon\Carbon::parse($request->get('from_date'))->startOfDay()
            : now()->startOfYear();

        $toDate = $request->get('to_date')
            ? \Carbon\Carbon::parse($request->get('to_date'))->endOfDay()
            : now()->endOfDay();

        // ── Filters ───────────────────────────────────────────────
        $metalFilter  = $request->get('metal');
        $statusFilter = $request->get('status');

        // ── Fetch sales ───────────────────────────────────────────
        $salesQuery = \App\Models\SalesTransaction::with(['items', 'oldJewelleryItems'])
            ->where('contact_id', $contact->id)
            ->whereBetween('sale_date', [$fromDate->toDateString(), $toDate->toDateString()]);

        if ($statusFilter) {
            $salesQuery->where('status', $statusFilter);
        }

        $sales = $salesQuery->orderBy('sale_date', 'desc')->get();

        // ── Metal filter (collection level) ───────────────────────
        if ($metalFilter) {
            $sales = $sales->filter(function ($sale) use ($metalFilter) {
                return $sale->items->contains(fn($i) =>
                    strtolower($i->metal_type ?? '') === strtolower($metalFilter)
                );
            });
        }

        // ── Summary ───────────────────────────────────────────────
        $summary = [
            'total_bills'         => $sales->count(),
            'total_amount'        => $sales->sum('total_amount'),
            'total_paid'          => $sales->sum('paid_amount'),
            'total_advance_used'  => $sales->sum('advance_applied'),
            'total_oj'            => $sales->sum('old_jewellery_exchange'),
            'gold_gross_weight'   => 0,
            'gold_net_weight'     => 0,
            'silver_gross_weight' => 0,
            'silver_net_weight'   => 0,
            'total_pieces'        => 0,
        ];

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $metal = strtolower($item->metal_type ?? '');
                $gw    = (float) ($item->gross_weight ?? 0);
                $nw    = (float) ($item->net_weight ?: $item->gross_weight ?? 0);
                $summary['total_pieces'] += (int) ($item->quantity ?? 1);
                if ($metal === 'gold') {
                    $summary['gold_gross_weight'] += $gw;
                    $summary['gold_net_weight']   += $nw;
                } elseif ($metal === 'silver') {
                    $summary['silver_gross_weight'] += $gw;
                    $summary['silver_net_weight']   += $nw;
                }
            }
        }

        // ── Excel export ──────────────────────────────────────────
        if ($request->get('export') === 'excel') {
            return $this->exportLedgerExcel($contact, $sales, $summary, $fromDate, $toDate);
        }

        return view('contacts.customer-ledger', compact(
            'contact', 'sales', 'summary', 'fromDate', 'toDate',
            'metalFilter', 'statusFilter'
        ));
    }

    // ─── Excel Export helper ──────────────────────────────────────────
    private function exportLedgerExcel(
        Contact $contact,
        $sales,
        array $summary,
        \Carbon\Carbon $fromDate,
        \Carbon\Carbon $toDate
    ) {
        $filename = 'ledger-' . str_replace(' ', '-', strtolower($contact->name))
            . '-' . $fromDate->format('Ymd')
            . '-to-' . $toDate->format('Ymd')
            . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($contact, $sales, $summary, $fromDate, $toDate) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Customer Ledger Report']);
            fputcsv($handle, ['Customer', $contact->name]);
            fputcsv($handle, ['Phone',    $contact->phone ?? '']);
            fputcsv($handle, ['Period',   $fromDate->format('d M Y') . ' to ' . $toDate->format('d M Y')]);
            fputcsv($handle, []);

            fputcsv($handle, ['SUMMARY']);
            fputcsv($handle, ['Total Bills',         $summary['total_bills']]);
            fputcsv($handle, ['Total Amount',        number_format($summary['total_amount'], 2)]);
            fputcsv($handle, ['Total Paid',          number_format($summary['total_paid'], 2)]);
            fputcsv($handle, ['Advance Used',        number_format($summary['total_advance_used'], 2)]);
            fputcsv($handle, ['OJ Exchange',         number_format($summary['total_oj'], 2)]);
            fputcsv($handle, ['Total Pieces',        $summary['total_pieces']]);
            fputcsv($handle, ['Gold Gross Weight',   number_format($summary['gold_gross_weight'], 3) . ' g']);
            fputcsv($handle, ['Gold Net Weight',     number_format($summary['gold_net_weight'], 3) . ' g']);
            fputcsv($handle, ['Silver Gross Weight', number_format($summary['silver_gross_weight'], 3) . ' g']);
            fputcsv($handle, ['Silver Net Weight',   number_format($summary['silver_net_weight'], 3) . ' g']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Invoice No', 'Date', 'Items', 'Pieces',
                'Gold Gross (g)', 'Gold Net (g)',
                'Silver Gross (g)', 'Silver Net (g)',
                'Subtotal', 'CGST', 'SGST',
                'OJ Exchange', 'Advance Used',
                'Total Amount', 'Paid Amount',
                'Payment Type', 'Status', 'Notes',
            ]);

            foreach ($sales as $sale) {
                $goldGw = $goldNw = $silverGw = $silverNw = $pieces = 0;
                $itemNames = [];
                foreach ($sale->items as $item) {
                    $metal = strtolower($item->metal_type ?? '');
                    $gw    = (float) ($item->gross_weight ?? 0);
                    $nw    = (float) ($item->net_weight ?: $item->gross_weight ?? 0);
                    $pieces += (int) ($item->quantity ?? 1);
                    $itemNames[] = $item->product_name;
                    if ($metal === 'gold')   { $goldGw += $gw; $goldNw += $nw; }
                    if ($metal === 'silver') { $silverGw += $gw; $silverNw += $nw; }
                }

                fputcsv($handle, [
                    $sale->invoice_no,
                    $sale->sale_date->format('d-m-Y'),
                    implode(', ', $itemNames),
                    $pieces,
                    number_format($goldGw,   3),
                    number_format($goldNw,   3),
                    number_format($silverGw, 3),
                    number_format($silverNw, 3),
                    number_format($sale->subtotal,                    2),
                    number_format($sale->cgst_amount,                 2),
                    number_format($sale->sgst_amount,                 2),
                    number_format($sale->old_jewellery_exchange ?? 0, 2),
                    number_format($sale->advance_applied        ?? 0, 2),
                    number_format($sale->total_amount,                2),
                    number_format($sale->paid_amount,                 2),
                    ucfirst($sale->payment_type ?? 'cash'),
                    ucfirst($sale->status       ?? ''),
                    $sale->notes ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── Supplier Ledger ──────────────────────────────────────────────
    public function supplierLedger(Request $request, Contact $contact)
    {
        abort_if($contact->type !== 'supplier', 404);
 
        // ── Date range ────────────────────────────────────────────
        $fromDate = $request->get('from_date')
            ? \Carbon\Carbon::parse($request->get('from_date'))->startOfDay()
            : now()->startOfYear();
 
        $toDate = $request->get('to_date')
            ? \Carbon\Carbon::parse($request->get('to_date'))->endOfDay()
            : now()->endOfDay();
 
        // ── Filters ───────────────────────────────────────────────
        $metalFilter  = $request->get('metal');   // gold / silver / ''
        $statusFilter = $request->get('status');  // pending / partially_split / fully_split / ''
 
        // ── Fetch purchases ───────────────────────────────────────
        $query = \App\Models\BulkPurchase::with(['items'])
            ->where('contact_id', $contact->id)
            ->whereBetween('purchase_date', [$fromDate->toDateString(), $toDate->toDateString()]);
 
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
 
        $purchases = $query->orderBy('purchase_date', 'desc')->get();
 
        // ── Metal filter (collection level) ───────────────────────
        if ($metalFilter) {
            $purchases = $purchases->filter(function ($p) use ($metalFilter) {
                return $p->items->contains(fn($i) =>
                    strtolower($i->metal_type ?? '') === strtolower($metalFilter)
                );
            });
        }
 
        // ── Summary ───────────────────────────────────────────────
        $summary = [
            'total_purchases'     => $purchases->count(),
            'total_amount'        => $purchases->sum('net_payable') ?: $purchases->sum('grand_total'),
            'total_taxable'       => $purchases->sum('grand_taxable'),
            'total_tax'           => $purchases->sum('grand_tax'),
            'total_tds'           => $purchases->sum('tds_amount'),
            'total_tcs'           => $purchases->sum('tcs_amount'),
            'total_discount'      => $purchases->sum('discount_amount'),
 
            // Weight by metal
            'gold_gross_weight'   => 0,
            'silver_gross_weight' => 0,
            'total_pieces'        => 0,
        ];
 
        foreach ($purchases as $purchase) {
            foreach ($purchase->items as $item) {
                $metal = strtolower($item->metal_type ?? '');
                $gw    = (float) ($item->gross_weight ?? 0);
                $summary['total_pieces'] += (int) ($item->pieces ?? 0);
                if ($metal === 'gold') {
                    $summary['gold_gross_weight']   += $gw;
                } elseif ($metal === 'silver') {
                    $summary['silver_gross_weight'] += $gw;
                }
            }
        }
 
        // ── Excel export ──────────────────────────────────────────
        if ($request->get('export') === 'excel') {
            return $this->exportSupplierLedgerExcel($contact, $purchases, $summary, $fromDate, $toDate);
        }
 
        return view('contacts.supplier-ledger', compact(
            'contact', 'purchases', 'summary', 'fromDate', 'toDate',
            'metalFilter', 'statusFilter'
        ));
    }
 
    // ─── Supplier Ledger Excel Export ────────────────────────────────
    private function exportSupplierLedgerExcel(
        Contact $contact,
        $purchases,
        array $summary,
        \Carbon\Carbon $fromDate,
        \Carbon\Carbon $toDate
    ) {
        $filename = 'supplier-ledger-' . str_replace(' ', '-', strtolower($contact->name))
            . '-' . $fromDate->format('Ymd')
            . '-to-' . $toDate->format('Ymd')
            . '.csv';
 
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
 
        $callback = function () use ($contact, $purchases, $summary, $fromDate, $toDate) {
            $handle = fopen('php://output', 'w');
 
            // ── Header info ──
            fputcsv($handle, ['Supplier Ledger Report']);
            fputcsv($handle, ['Supplier', $contact->name]);
            fputcsv($handle, ['Phone',    $contact->phone ?? '']);
            fputcsv($handle, ['GSTIN',    $contact->gstin ?? '']);
            fputcsv($handle, ['Period',   $fromDate->format('d M Y') . ' to ' . $toDate->format('d M Y')]);
            fputcsv($handle, []);
 
            // ── Summary block ──
            fputcsv($handle, ['SUMMARY']);
            fputcsv($handle, ['Total Purchases',    $summary['total_purchases']]);
            fputcsv($handle, ['Total Taxable',      number_format($summary['total_taxable'],  2)]);
            fputcsv($handle, ['Total Tax',          number_format($summary['total_tax'],      2)]);
            fputcsv($handle, ['Total Discount',     number_format($summary['total_discount'], 2)]);
            fputcsv($handle, ['Total TDS',          number_format($summary['total_tds'],      2)]);
            fputcsv($handle, ['Total TCS',          number_format($summary['total_tcs'],      2)]);
            fputcsv($handle, ['Net Payable',        number_format($summary['total_amount'],   2)]);
            fputcsv($handle, ['Total Pieces',       $summary['total_pieces']]);
            fputcsv($handle, ['Gold Weight (g)',    number_format($summary['gold_gross_weight'],   3)]);
            fputcsv($handle, ['Silver Weight (g)',  number_format($summary['silver_gross_weight'], 3)]);
            fputcsv($handle, []);
 
            // ── Column headers ──
            fputcsv($handle, [
                'Purchase No', 'Date', 'Invoice No', 'Metals',
                'Pieces', 'Gold (g)', 'Silver (g)',
                'Taxable', 'Tax', 'Discount', 'TDS', 'TCS',
                'Net Payable', 'Status', 'Notes',
            ]);
 
            // ── Rows ──
            foreach ($purchases as $p) {
                $goldGw = $silverGw = $pieces = 0;
                $metals = [];
                foreach ($p->items as $item) {
                    $metal = strtolower($item->metal_type ?? '');
                    $pieces += (int) ($item->pieces ?? 0);
                    $metals[] = ucfirst($metal) . ($item->purity ? ' '.$item->purity : '');
                    if ($metal === 'gold')   $goldGw   += (float)($item->gross_weight ?? 0);
                    if ($metal === 'silver') $silverGw += (float)($item->gross_weight ?? 0);
                }
 
                fputcsv($handle, [
                    $p->purchase_no,
                    $p->purchase_date->format('d-m-Y'),
                    $p->invoice_no ?? '',
                    implode(' + ', array_unique($metals)),
                    $pieces,
                    number_format($goldGw,   3),
                    number_format($silverGw, 3),
                    number_format($p->grand_taxable  ?? 0, 2),
                    number_format($p->grand_tax      ?? 0, 2),
                    number_format($p->discount_amount ?? 0, 2),
                    number_format($p->tds_amount      ?? 0, 2),
                    number_format($p->tcs_amount      ?? 0, 2),
                    number_format($p->net_payable ?? $p->grand_total ?? 0, 2),
                    ucfirst(str_replace('_', ' ', $p->status ?? '')),
                    $p->notes ?? '',
                ]);
            }
 
            fclose($handle);
        };
 
        return response()->stream($callback, 200, $headers);
    }


// ─── Download sample CSV template (Customers only) ──────────────────
    public function downloadSample()
    {
        $filename = 'customers-import-sample.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        // '*' marks a required field — stripped automatically on import
        $columns = [
            'name*', 'email', 'phone', 'whatsapp',
            'date_of_birth', 'gender', 'address', 'city', 'state',
            'country', 'pincode', 'gstin', 'credit_limit',
            'aadhaar_number', 'pan_number',
        ];

        $sampleRows = [
            ['Rajesh Kumar', 'rajesh@example.com', '9876543210', '9876543210', '1990-05-14', 'male', '12 MG Road', 'Chennai', 'Tamil Nadu', 'India', '600001', '33AAAAA0000A1Z5', '50000', '', ''],
            ['Priya Selvam', 'priya@example.com', '9123456780', '9123456780', '1988-11-02', 'female', '45 Bazaar St', 'Madurai', 'Tamil Nadu', 'India', '625001', '', '0', '', ''],
        ];

        $callback = function () use ($columns, $sampleRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            foreach ($sampleRows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── Bulk import Customers from CSV (no package) ─────────────────────
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file   = $request->file('import_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Strip '*' / '(...)' markers from header, normalize to lowercase
        $rawHeader = fgetcsv($handle);
        $header = array_map(function ($h) {
            $h = preg_replace('/\(.*?\)/', '', $h);
            $h = str_replace('*', '', $h);
            return strtolower(trim($h));
        }, $rawHeader);

        if (!in_array('name', $header)) {
            fclose($handle);
            return response()->json([
                'status'  => 'error',
                'message' => "Invalid file — missing required column 'name'. Please use the sample template.",
            ], 422);
        }

        $imported  = 0;
        $errorList = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));
            $data = array_map(fn ($v) => $v === '' ? null : trim((string) $v), $data);

            $validator = \Validator::make($data, [
                'name'           => 'required|string|max:255',
                'email'          => 'nullable|email|unique:contacts,email',
                'phone'          => 'nullable|regex:/^[0-9]{10}$/|unique:contacts,phone',
                'whatsapp'       => 'nullable|regex:/^[0-9]{10}$/',
                'aadhaar_number' => 'nullable|regex:/^[0-9]{12}$/|unique:contacts,aadhaar_number',
                'pan_number'     => 'nullable|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/|unique:contacts,pan_number',
                'gender'         => 'nullable|in:male,female,other',
            ]);

            if ($validator->fails()) {
                $errorList[] = [
                    'row'    => $rowNumber,
                    'errors' => implode(' ', $validator->errors()->all()),
                ];
                continue;
            }

            $contact = Contact::create([
                'name'            => $data['name'],
                'code'            => Contact::generateCode(), // reuse existing generator
                'type'            => 'customer',
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'] ?? null,
                'whatsapp'        => $data['whatsapp'] ?? null,
                'date_of_birth'   => $data['date_of_birth'] ?? null,
                'gender'          => $data['gender'] ?? null,
                'address'         => $data['address'] ?? null,
                'city'            => $data['city'] ?? null,
                'state'           => $data['state'] ?? null,
                'country'         => $data['country'] ?? null,
                'pincode'         => $data['pincode'] ?? null,
                'gstin'           => $data['gstin'] ?? null,
                'credit_limit'    => $data['credit_limit'] ?? 0,
                'opening_balance' => 0,
                'notes'           => null,
                'status'          => 'active',
                'aadhaar_number'  => $data['aadhaar_number'] ?? null,
                'pan_number'      => $data['pan_number'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            ActivityLog::log(
                'contact', 'created', $contact->id, $contact->name,
                [], $contact->only($this->fields())
            );

            $imported++;
        }

        fclose($handle);

        return response()->json([
            'status'   => count($errorList) > 0 ? 'partial' : 'success',
            'message'  => "{$imported} customer(s) imported successfully." .
                          (count($errorList) ? ' ' . count($errorList) . ' row(s) skipped due to errors.' : ''),
            'imported' => $imported,
            'failed'   => count($errorList),
            'errors'   => $errorList,
        ]);
    }


}