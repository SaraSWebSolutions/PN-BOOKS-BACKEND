<?php

// ============================================================
//  config/business.php
//  Access anywhere via:  config('business.name')  etc.
// ============================================================

return [
    'name'     => env('BUSINESS_NAME',    env('APP_NAME', 'Your Business')),
    'address'  => env('BUSINESS_ADDRESS', ''),
    'city'     => env('BUSINESS_CITY',    ''),
    'state'    => env('BUSINESS_STATE',   ''),
    'pincode'  => env('BUSINESS_PINCODE', ''),
    'phone'    => env('BUSINESS_PHONE',   ''),
    'gstin'    => env('BUSINESS_GSTIN',   ''),
    'email'    => env('BUSINESS_EMAIL',   ''),
    'tagline'  => env('BUSINESS_TAGLINE', ''),

    // Full address helper
    'full_address' => implode(', ', array_filter([
        env('BUSINESS_ADDRESS', ''),
        env('BUSINESS_CITY',    ''),
        env('BUSINESS_STATE',   ''),
        env('BUSINESS_PINCODE', ''),
    ])),
];