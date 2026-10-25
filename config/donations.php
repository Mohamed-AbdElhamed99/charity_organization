<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum donation (cents)
    |--------------------------------------------------------------------------
    */
    'min_amount_cents' => (int) env('DONATION_MIN_AMOUNT_CENTS', 100),

    /*
    |--------------------------------------------------------------------------
    | Default USD account for Stripe settlement ledger entries
    |--------------------------------------------------------------------------
    */
    'stripe_account_id' => env('DONATION_STRIPE_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Export row threshold — above this, exports are queued
    |--------------------------------------------------------------------------
    */
    'export_sync_max_rows' => (int) env('DONATION_EXPORT_SYNC_MAX_ROWS', 5000),

    /*
    |--------------------------------------------------------------------------
    | Organization line shown on shared campaign appeals
    |--------------------------------------------------------------------------
    */
    'organization' => [
        'legal_name' => env('DONATION_ORG_LEGAL_NAME', 'New Egypt Group Inc.'),
        'tax_status' => env('DONATION_ORG_TAX_STATUS', '501(c)(3)'),
        'ein' => env('DONATION_ORG_EIN', '81-2019298'),
        'website' => env('DONATION_ORG_WEBSITE', 'www.newegyptgroup.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zelle scan-to-pay shown on campaign appeal pages
    |--------------------------------------------------------------------------
    |
    | The QR identifies the organization. It does not lock an amount or notify
    | this application when a donor pays.
    |
    */
    'zelle' => [
        'email' => env('DONATION_ZELLE_EMAIL'),
        'qr' => 'images/zelle-qr.png',
    ],

];
