<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;

class ContentPreferenceApiController extends Controller
{
    // GET /api/profile/content-preferences
    public function show(Request $request)
    {
        $user = $request->user();

        $profile = $user->customerProfile()
            ->with(['interestedCategories:id,name_en,name_ms', 'language:id,name,code'])
            ->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'show_recommendations' => (bool) $profile->show_recommendations,
                'new_release_alerts'   => (bool) $profile->new_release_alerts,
                'interested_categories' => $profile->interestedCategories,
                'preferred_language'    => $profile->language,
            ],
        ]);
    }

    // PUT /api/profile/content-preferences
    public function update(Request $request)
    {
        $data = $request->validate([
            'category_ids'           => 'nullable|array',
            'category_ids.*'         => 'integer|exists:categories,id',
            'language_id'            => 'nullable|integer|exists:languages,id',
            'show_recommendations'   => 'nullable|boolean',
            'new_release_alerts'     => 'nullable|boolean',
        ]);

        $user = $request->user();

        $profile = $user->customerProfile ?? CustomerProfile::create(['user_id' => $user->id]);

        $updateData = collect($data)->only([
            'language_id', 'show_recommendations', 'new_release_alerts',
        ])->toArray();

        // cast boolean fields properly (form-data sends "true"/"1" as strings)
        foreach (['show_recommendations', 'new_release_alerts'] as $boolField) {
            if ($request->has($boolField)) {
                $updateData[$boolField] = $request->boolean($boolField);
            }
        }

        $profile->update($updateData);

        if (array_key_exists('category_ids', $data)) {
            $profile->interestedCategories()->sync($data['category_ids'] ?? []);
        }

        $profile->load(['interestedCategories:id,name_en,name_ms', 'language:id,name,code']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Content preferences updated successfully',
            'data' => [
                'show_recommendations' => (bool) $profile->show_recommendations,
                'new_release_alerts'   => (bool) $profile->new_release_alerts,
                'interested_categories' => $profile->interestedCategories,
                'preferred_language'    => $profile->language,
            ],
        ]);
    }
}