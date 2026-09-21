<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportSubject;

class SupportSubjectApiController extends Controller
{
    // GET /api/support/subjects — populates the "Select a subject…" dropdown
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SupportSubject::active()->ordered()->get(['id', 'name']),
        ]);
    }
}