<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageApiController extends Controller
{
    /**
     * GET /api/languages
     * Public list of active languages (English / Malay for SG), for
     * register / profile-update dropdowns.
     */
    public function index(Request $request)
    {
        $languages = Language::active()
            ->ordered()
            ->get(['id', 'name', 'native_name', 'code']);

        return response()->json([
            'status' => 'success',
            'data'   => $languages,
        ]);
    }
}