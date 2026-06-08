<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CampaignCategoryController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CampaignExpenseController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\LegalDocumentController;
use App\Http\Controllers\Admin\NewsCategoryController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::redirect('/', '/admin/dashboard');
        Route::inertia('dashboard', 'admin/dashboard')->name('dashboard');
        Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
        Route::post('news/bulk-destroy', [NewsController::class, 'bulkDestroy'])->name('news.bulk-destroy');
        Route::post('news/{id}/restore', [NewsController::class, 'restore'])->name('news.restore');
        Route::resource('news', NewsController::class)->except(['show', 'create', 'edit']);
        Route::post('news-categories/bulk-destroy', [NewsCategoryController::class, 'bulkDestroy'])->name('news-categories.bulk-destroy');
        Route::post('news-categories/{id}/restore', [NewsCategoryController::class, 'restore'])->name('news-categories.restore');
        Route::resource('news-categories', NewsCategoryController::class)->except(['show', 'create', 'edit']);
        Route::post('campaign-categories/bulk-destroy', [CampaignCategoryController::class, 'bulkDestroy'])->name('campaign-categories.bulk-destroy');
        Route::post('campaign-categories/{id}/restore', [CampaignCategoryController::class, 'restore'])->name('campaign-categories.restore');
        Route::resource('campaign-categories', CampaignCategoryController::class)->except(['show', 'create', 'edit']);
        Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
        Route::resource('campaigns', CampaignController::class)->except(['create', 'edit']);
        Route::get('campaigns/{campaign}/expenses', [CampaignExpenseController::class, 'campaignIndex'])->name('campaigns.expenses.index');
        Route::get('campaign-expenses', [CampaignExpenseController::class, 'index'])->name('campaign-expenses.index');
        Route::post('campaign-expenses', [CampaignExpenseController::class, 'store'])->name('campaign-expenses.store');
        Route::patch('campaign-expenses/{expense}', [CampaignExpenseController::class, 'update'])->name('campaign-expenses.update');
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::post('transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');
        Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
        Route::post('accounts/bulk-destroy', [AccountController::class, 'bulkDestroy'])->name('accounts.bulk-destroy');
        Route::post('accounts/{id}/restore', [AccountController::class, 'restore'])->name('accounts.restore');
        Route::resource('accounts', AccountController::class)->except(['show', 'create', 'edit']);
        Route::post('faqs/bulk-destroy', [FaqController::class, 'bulkDestroy'])->name('faqs.bulk-destroy');
        Route::post('faqs/{id}/restore', [FaqController::class, 'restore'])->name('faqs.restore');
        Route::resource('faqs', FaqController::class)->except(['show', 'create', 'edit']);
        Route::get('legal/terms', [LegalDocumentController::class, 'editTerms'])->name('legal.terms.edit');
        Route::patch('legal/terms', [LegalDocumentController::class, 'updateTerms'])->name('legal.terms.update');
        Route::get('legal/privacy', [LegalDocumentController::class, 'editPrivacy'])->name('legal.privacy.edit');
        Route::patch('legal/privacy', [LegalDocumentController::class, 'updatePrivacy'])->name('legal.privacy.update');
        Route::post('contact-messages/bulk-destroy', [ContactMessageController::class, 'bulkDestroy'])->name('contact-messages.bulk-destroy');
        Route::patch('contact-messages/{contactMessage}/mark-reviewed', [ContactMessageController::class, 'markReviewed'])->name('contact-messages.mark-reviewed');
        Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
    });
});

require __DIR__.'/settings.php';
