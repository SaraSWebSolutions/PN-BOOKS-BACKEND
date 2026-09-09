<?php
// app/Http/Controllers/CountryController.php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    private function rules(?int $ignoreId = null): array
    {
        return [
            'name'       => 'required|string|max:150',
            'code'       => 'required|string|max:5',
            'phone_code' => 'nullable|string|max:10',
            'status'     => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $countries = Country::ordered()->get();

        $stats = [
            'total'    => Country::count(),
            'active'   => Country::active()->count(),
            'inactive' => Country::where('status', false)->count(),
        ];

        return view('countries.index', compact('countries', 'stats'));
    }

    // Used by the Currency form to load countries for the dropdown (AJAX, optional)
    public function activeList(): JsonResponse
    {
        return response()->json(
            Country::active()->ordered()->get(['id', 'name', 'code'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'name' => $this->rules()['name'] . '|unique:countries,name',
            'code' => $this->rules()['code'] . '|unique:countries,code',
        ]));

        $country = Country::create([
            'name'       => trim($request->name),
            'code'       => strtoupper(trim($request->code)),
            'phone_code' => $request->phone_code,
            'status'     => $request->boolean('status', true),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Country '{$country->name}' created! 🎉",
            'country' => $country,
        ]);
    }

    public function update(Request $request, Country $country): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'name' => $this->rules()['name'] . '|unique:countries,name,' . $country->id,
            'code' => $this->rules()['code'] . '|unique:countries,code,' . $country->id,
        ]));

        $country->update([
            'name'       => trim($request->name),
            'code'       => strtoupper(trim($request->code)),
            'phone_code' => $request->phone_code,
            'status'     => $request->boolean('status', $country->status),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Country '{$country->name}' updated! ✅",
            'country' => $country->fresh(),
        ]);
    }

    public function toggleStatus(Country $country): JsonResponse
    {
        $country->update([
            'status' => ! $country->status,
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$country->name}' " . ($country->status ? 'activated' : 'deactivated') . '.',
            'is_active' => $country->status,
        ]);
    }

    public function destroy(Country $country): JsonResponse
    {
        if ($country->currencies()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete — country has currencies linked to it.',
            ], 422);
        }

        $name = $country->name;
        $country->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }
}