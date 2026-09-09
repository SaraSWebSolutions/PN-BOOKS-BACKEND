<?php
return [
    'enabled'       => env('EINVOICE_ENABLED', false),
    'gstin'         => env('EINVOICE_GSTIN'),
    'username'      => env('EINVOICE_USERNAME'),
    'password'      => env('EINVOICE_PASSWORD'),
    'client_id'     => env('EINVOICE_CLIENT_ID'),
    'client_secret' => env('EINVOICE_CLIENT_SECRET'),

    // ✅ Exact URLs from your portal screenshot
    'auth_url'      => 'https://api.einvoice1.gst.gov.in/eivital/v1.04/auth',
    'generate_url'  => 'https://api.einvoice1.gst.gov.in/eicore/v1.03/Invoice',
    'cancel_url'    => 'https://api.einvoice1.gst.gov.in/eicore/v1.03/Invoice/Cancel',
    'get_irn_url'   => 'https://api.einvoice1.gst.gov.in/eicore/v1.03/Invoice/irn',
];