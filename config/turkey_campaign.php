<?php

/*
|--------------------------------------------------------------------------
| Free Halal Turkey campaign (temporary, hardcoded)
|--------------------------------------------------------------------------
|
| Set TURKEY_CAMPAIGN_ENABLED=false once the campaign is over to hide the
| public form and verification page. Admin records stay intact.
|
*/
return [

    'enabled' => (bool) env('TURKEY_CAMPAIGN_ENABLED', true),

    /** Total number of turkeys available. */
    'total' => (int) env('TURKEY_CAMPAIGN_TOTAL', 50),

    /** Slug of the hidden (non-public) campaign that supports are recorded under. */
    'campaign_slug' => 'free-halal-turkey',
    'campaign_title_en' => 'Free Halal Turkey',
    'campaign_title_ar' => 'ديك رومي حلال مجاني',

    /** Prefix of the verification code handed to beneficiaries at delivery. */
    'code_prefix' => 'TRK-',

    /** Optional staff user id recorded as creator of the beneficiaries/campaign/supports. */
    'created_by' => env('TURKEY_CAMPAIGN_CREATED_BY'),

    /** WhatsApp chat link, e.g. https://wa.me/1XXXXXXXXXX */
    'whatsapp_url' => env('TURKEY_WHATSAPP_URL'),

    /** Share image (relative to /public). */
    'share_image' => '/images/turkey-campaign.jpg',
    'share_image_width' => 819,
    'share_image_height' => 1024,
];
