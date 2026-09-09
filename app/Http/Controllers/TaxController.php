<?php
// app/Http/Controllers/TaxController.php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaxController extends Controller
{
    private function rules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id',
            'tax_name'   => 'required|string|max:100',
            'tax_code'   => 'required|string|max:20',
            'tax_type'   => 'required|in:percentage,fixed',
            'tax_rate'   => 'required|numeric|min:0|max:999.999',
            'status'     => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $taxes     = Tax::with('country')->ordered()->get();
        $countries = Country::active()->ordered()->get();

        $stats = [
            'total'    => Tax::count(),
            'active'   => Tax::active()->count(),
            'inactive' => Tax::where('status', false)->count(),
        ];

        return view('taxes.index', compact('taxes', 'countries', 'stats'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'tax_code' => $this->rules()['tax_code'] . '|unique:taxes,tax_code',
        ]));

        $tax = Tax::create([
            'country_id' => $request->country_id,
            'tax_name'   => trim($request->tax_name),
            'tax_code'   => strtoupper(trim($request->tax_code)),
            'tax_type'   => $request->tax_type,
            'tax_rate'   => $request->tax_rate,
            'status'     => $request->boolean('status', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Tax '{$tax->tax_name}' created! 🎉",
            'tax'     => $tax->load('country'),
        ]);
    }

    public function update(Request $request, Tax $tax): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'tax_code' => $this->rules()['tax_code'] . '|unique:taxes,tax_code,' . $tax->id,
        ]));

        $tax->update([
            'country_id' => $request->country_id,
            'tax_name'   => trim($request->tax_name),
            'tax_code'   => strtoupper(trim($request->tax_code)),
            'tax_type'   => $request->tax_type,
            'tax_rate'   => $request->tax_rate,
            'status'     => $request->boolean('status', $tax->status),
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Tax '{$tax->tax_name}' updated! ✅",
            'tax'     => $tax->fresh()->load('country'),
        ]);
    }

    public function toggleStatus(Tax $tax): JsonResponse
    {
        $tax->update([
            'status'     => ! $tax->status,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$tax->tax_name}' " . ($tax->status ? 'activated' : 'deactivated') . '.',
            'is_active' => $tax->status,
        ]);
    }

    public function destroy(Tax $tax): JsonResponse
    {
        $name = $tax->tax_name;
        $tax->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }
}