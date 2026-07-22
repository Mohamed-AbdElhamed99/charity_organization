<?php

use App\Http\Controllers\Site\CampaignController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\DonationController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LegalDocumentController;
use App\Http\Controllers\Site\LocaleController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');
    
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/lang/{locale}', LocaleController::class)->name('lang.switch');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{campaign:slug}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/donations', [DonationController::class, 'index'])->name('donations.index');
Route::get('/donations/campaigns-list', [DonationController::class, 'donatableCampaignsList'])->name('donations.campaigns-list');
Route::get('/donations/saved-payment-methods', [DonationController::class, 'savedPaymentMethodsList'])->name('donations.saved-payment-methods');
Route::get('/campaigns/{campaign:slug}/donate', [DonationController::class, 'campaignDonate'])->name('campaigns.donate');
Route::get('/donate', [DonationController::class, 'generalDonate'])->name('donate.general');
Route::post('/donations/intent', [DonationController::class, 'storeIntent'])
    ->middleware('throttle:10,1')
    ->name('donations.intent');
Route::post('/donations/subscribe', [DonationController::class, 'storeSubscription'])
    ->middleware('throttle:10,1')
    ->name('donations.subscribe');
Route::get('/donations/{paymentIntentId}/thank-you', [DonationController::class, 'thankYou'])->name('donations.thank-you');
Route::get('/donations/{paymentIntentId}/status', [DonationController::class, 'status'])->name('donations.status');
Route::get('/donations/subscriptions/{stripeSubscriptionId}/portal', [DonationController::class, 'subscriptionPortal'])
    ->middleware('auth')
    ->name('donations.subscriptions.portal');
Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
Route::get('/terms', [LegalDocumentController::class, 'terms'])->name('terms');
Route::get('/privacy', [LegalDocumentController::class, 'privacy'])->name('privacy');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');
