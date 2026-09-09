<?php
// app/Http/Controllers/CurrencyController.php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
   private function rules(): array
{
    return [
        'country_id' => 'nullable|exists:countries,id',
        'code'       => 'required|string|max:10',
        'name'       => 'required|string|max:100',
        'symbol'     => 'required|string|max:10',
        'status'     => 'nullable|boolean',
    ];
}

public function index()
{
    $currencies = Currency::with('country')->ordered()->get();
    $countries  = Country::active()->ordered()->get(); // pass to view for dropdown

    $stats = [
        'total'    => Currency::count(),
        'active'   => Currency::active()->count(),
        'inactive' => Currency::where('status', false)->count(),
    ];

    return view('currencies.index', compact('currencies', 'countries', 'stats'));
}

public function store(Request $request): JsonResponse
{
    $request->validate(array_merge($this->rules(), [
        'code' => $this->rules()['code'] . '|unique:currencies,code',
        'name' => $this->rules()['name'] . '|unique:currencies,name',
    ]));

    $currency = Currency::create([
        'country_id' => $request->country_id,
        'code'       => strtoupper(trim($request->code)),
        'name'       => trim($request->name),
        'symbol'     => trim($request->symbol),
        'status'     => $request->boolean('status', true),
    ]);

    return response()->json([
        'status'   => 'success',
        'message'  => "Currency '{$currency->name}' created! 🎉",
        'currency' => $currency->load('country'),
    ]);
}

public function update(Request $request, Currency $currency): JsonResponse
{
    $request->validate(array_merge($this->rules(), [
        'code' => $this->rules()['code'] . '|unique:currencies,code,' . $currency->id,
        'name' => $this->rules()['name'] . '|unique:currencies,name,' . $currency->id,
    ]));

    $currency->update([
        'country_id' => $request->country_id,
        'code'       => strtoupper(trim($request->code)),
        'name'       => trim($request->name),
        'symbol'     => trim($request->symbol),
        'status'     => $request->boolean('status', $currency->status),
    ]);

    return response()->json([
        'status'   => 'success',
        'message'  => "Currency '{$currency->name}' updated! ✅",
        'currency' => $currency->fresh()->load('country'),
    ]);
}

    public function toggleStatus(Currency $currency): JsonResponse
    {
        $currency->update([
            'status' => ! $currency->status,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "'{$currency->name}' " . ($currency->status ? 'activated' : 'deactivated') . '.',
            'is_active' => $currency->status,
        ]);
    }

    public function destroy(Currency $currency): JsonResponse
    {
        // If you later relate currencies to books/orders, guard the delete like this:
        // if ($currency->books()->exists()) {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => 'Cannot delete — currency is in use.',
        //     ], 422);
        // }

        $name = $currency->name;
        $currency->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }
}