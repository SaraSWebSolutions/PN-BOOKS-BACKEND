<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryApiController extends Controller
{
    /**
     * GET /api/countries
     * Public list of active countries, for register / profile-update dropdowns.
     */
    public function index(Request $request)
    {
        $countries = Country::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'phone_code']);

        return response()->json([
            'status' => 'success',
            'data'   => $countries,
        ]);
    }
}