<?php

namespace App\Http\Controllers;

use App\Models\AuthorProfile;
use App\Models\Category;
use App\Models\PublisherProfile;
use App\Models\Series;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class QuickCreateController extends Controller
{
    public function category(Request $request)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:150',
            'name_ms' => 'nullable|string|max:150',
        ]);

        try {
            $category = Category::create([
                'name_en'    => $data['name_en'],
                'name_ms'    => $data['name_ms'] ?? null,
                'is_active'  => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'item'   => ['id' => $category->id, 'name' => $category->name_en],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function subcategory(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name_en'     => 'required|string|max:150',
            'name_ms'     => 'nullable|string|max:150',
        ]);

        try {
            $sub = Subcategory::create([
                'category_id' => $data['category_id'],
                'name_en'     => $data['name_en'],
                'name_ms'     => $data['name_ms'] ?? null,
                'is_active'   => true,
                'created_by'  => Auth::id(),
                'updated_by'  => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'item'   => ['id' => $sub->id, 'name' => $sub->name],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function series(Request $request)
    {
        $data = $request->validate([
            'publisher_id' => 'required|exists:publisher_profiles,id',
            'name'         => 'required|string|max:150',
        ]);

        try {
            $publisher = PublisherProfile::findOrFail($data['publisher_id']);

            $series = Series::create([
                'name'         => $data['name'],
                'publisher_id' => $publisher->user_id, // matches your existing schema
                'is_active'    => true,
                'created_by'   => Auth::id(),
                'updated_by'   => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'item'   => ['id' => $series->id, 'name' => $series->name],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function author(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
        ]);

        try {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make(str()->random(12)), // temp password; author resets later
                'status'   => 'active',
            ]);

            $author = AuthorProfile::create([
                'user_id'  => $user->id,
                'pen_name' => $data['name'],
                'status'   => true,
            ]);

            return response()->json([
                'status' => 'success',
                'item'   => ['id' => $author->id, 'name' => $author->pen_name],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function publisher(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:150',
            'email'        => 'required|email|unique:users,email',
        ]);

        try {
            $user = User::create([
                'name'     => $data['company_name'],
                'email'    => $data['email'],
                'password' => Hash::make(str()->random(12)),
                'status'   => 'active',
            ]);

            $publisher = PublisherProfile::create([
                'user_id'      => $user->id,
                'company_name' => $data['company_name'],
                'status'       => true,
            ]);

            return response()->json([
                'status' => 'success',
                'item'   => ['id' => $publisher->id, 'name' => $publisher->company_name],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}