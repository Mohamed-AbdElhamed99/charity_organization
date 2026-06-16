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

];
