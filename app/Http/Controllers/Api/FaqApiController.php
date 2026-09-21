<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqApiController extends Controller
{
    // GET /api/faqs
    // GET /api/faqs?type=help_support
    // GET /api/faqs?type=faq&search=password
    public function index(Request $request)
    {
        $request->validate([
            'type'   => 'nullable|in:help_support,faq',
            'search' => 'nullable|string|max:100',
        ]);

        $query = Faq::active()->ordered();

        if ($request->filled('type')) {
            $query->forType($request->type);   // matches that type + "both"
        }

        if ($request->filled('search')) {
            $query->where('question', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get(['id', 'question', 'answer', 'type']),
        ]);
    }
}