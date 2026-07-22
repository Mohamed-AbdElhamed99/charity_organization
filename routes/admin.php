<?php

use App\Enums\ModulePermission;
use App\Http\Controllers\Admin\AidItemController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\BeneficiaryAssessmentController;
use App\Http\Controllers\Admin\BeneficiaryController;
use App\Http\Controllers\Admin\BeneficiarySupportController;
use App\Http\Controllers\Admin\BeneficiarySupportReportController;
use App\Http\Controllers\Admin\CampaignBeneficiaryReportController;
use App\Http\Controllers\Admin\CampaignCategoryController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CampaignExpenseController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonationController;
use App\Http\Controllers\Admin\DonorProfileController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GeneralExpenseCategoryController;
use App\Http\Controllers\Admin\GeneralExpenseController;
use App\Http\Controllers\Admin\LegalDocumentController;
use App\Http\Controllers\Admin\MeetingAttachmentController;
use App\Http\Controllers\Admin\MeetingController;
use App\Http\Controllers\Admin\MeetingDecisionController;
use App\Http\Controllers\Admin\MeetingMinutesController;
use App\Http\Controllers\Admin\NewsCategoryController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureUserIsStaff;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'welcome')->name('home');

// `EnsureUserIsStaff` performs its own auth check and 404s unauthorized
// visitors (guests and non-staff users alike), so the admin panel's
// existence is never revealed to them.

// Builds the `permission:{name}` middleware string from the ModulePermission
// enum instead of hardcoding permission names, so renaming an ability in the
// enum/config automatically updates every route that guards it.
$perm = fn (ModulePermission $module, string $ability): string => 'permission:'.$module->permission($ability);

Route::middleware([EnsureUserIsStaff::class, 'verified'])->group(function () use ($perm) {
    Route::prefix('admin')->name('admin.')->group(function () use ($perm) {

        Route::redirect('/', '/admin/dashboard');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware($perm(ModulePermission::SYSTEM, 'access_dashboard'));

        // ==========================================================
        // Users
        // ==========================================================
        Route::prefix('users')->name('users.')->group(function () use ($perm) {
            Route::get('/', [UserController::class, 'index'])->name('index')->middleware($perm(ModulePermission::USERS, 'view'));
            Route::post('/', [UserController::class, 'store'])->name('store')->middleware($perm(ModulePermission::USERS, 'create'));
            Route::get('{user}', [UserController::class, 'show'])->name('show')->middleware($perm(ModulePermission::USERS, 'view'));
            Route::match(['put', 'patch'], '{user}', [UserController::class, 'update'])->name('update')->middleware($perm(ModulePermission::USERS, 'edit'));
            Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::USERS, 'delete'));
            Route::post('bulk-destroy', [UserController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::USERS, 'delete'));
            Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::USERS, 'delete'));
        });

        // ==========================================================
        // Roles
        // ==========================================================
        Route::prefix('roles')->name('roles.')->group(function () use ($perm) {
            Route::get('/', [RoleController::class, 'index'])->name('index')->middleware($perm(ModulePermission::ROLES, 'view'));
            Route::post('/', [RoleController::class, 'store'])->name('store')->middleware($perm(ModulePermission::ROLES, 'create'));
            Route::put('{role}', [RoleController::class, 'update'])->name('update')->middleware($perm(ModulePermission::ROLES, 'edit'));
            Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::ROLES, 'delete'));
        });

        // ==========================================================
        // News
        // ==========================================================
        Route::prefix('news')->name('news.')->group(function () use ($perm) {
            Route::get('/', [NewsController::class, 'index'])->name('index')->middleware($perm(ModulePermission::NEWS, 'view'));
            Route::post('/', [NewsController::class, 'store'])->name('store')->middleware($perm(ModulePermission::NEWS, 'create'));
            Route::put('{news}', [NewsController::class, 'update'])->name('update')->middleware($perm(ModulePermission::NEWS, 'edit'));
            Route::delete('{news}', [NewsController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::NEWS, 'delete'));
            Route::post('bulk-destroy', [NewsController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::NEWS, 'delete'));
            Route::post('{id}/restore', [NewsController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::NEWS, 'delete'));
        });

        // ==========================================================
        // News Categories
        // ==========================================================
        Route::prefix('news-categories')->name('news-categories.')->group(function () use ($perm) {
            Route::get('/', [NewsCategoryController::class, 'index'])->name('index')->middleware($perm(ModulePermission::NEWS_CATEGORIES, 'view'));
            Route::post('/', [NewsCategoryController::class, 'store'])->name('store')->middleware($perm(ModulePermission::NEWS_CATEGORIES, 'create'));
            Route::put('{news_category}', [NewsCategoryController::class, 'update'])->name('update')->middleware($perm(ModulePermission::NEWS_CATEGORIES, 'edit'));
            Route::delete('{news_category}', [NewsCategoryController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::NEWS_CATEGORIES, 'delete'));
            Route::post('bulk-destroy', [NewsCategoryController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::NEWS_CATEGORIES, 'delete'));
            Route::post('{id}/restore', [NewsCategoryController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::NEWS_CATEGORIES, 'delete'));
        });

        // ==========================================================
        // Campaign Categories
        // ==========================================================
        Route::prefix('campaign-categories')->name('campaign-categories.')->group(function () use ($perm) {
            Route::get('/', [CampaignCategoryController::class, 'index'])->name('index')->middleware($perm(ModulePermission::CAMPAIGN_CATEGORIES, 'view'));
            Route::post('/', [CampaignCategoryController::class, 'store'])->name('store')->middleware($perm(ModulePermission::CAMPAIGN_CATEGORIES, 'create'));
            Route::put('{campaign_category}', [CampaignCategoryController::class, 'update'])->name('update')->middleware($perm(ModulePermission::CAMPAIGN_CATEGORIES, 'edit'));
            Route::delete('{campaign_category}', [CampaignCategoryController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::CAMPAIGN_CATEGORIES, 'delete'));
            Route::post('bulk-destroy', [CampaignCategoryController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::CAMPAIGN_CATEGORIES, 'delete'));
            Route::post('{id}/restore', [CampaignCategoryController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::CAMPAIGN_CATEGORIES, 'delete'));
        });

        // ==========================================================
        // Campaigns
        // ==========================================================
        Route::prefix('campaigns')->name('campaigns.')->group(function () use ($perm) {
            Route::get('/', [CampaignController::class, 'index'])->name('index')->middleware($perm(ModulePermission::CAMPAIGNS, 'view'));
            Route::get('create', [CampaignController::class, 'create'])->name('create')->middleware($perm(ModulePermission::CAMPAIGNS, 'create'));
            Route::post('/', [CampaignController::class, 'store'])->name('store')->middleware($perm(ModulePermission::CAMPAIGNS, 'create'));
            Route::get('{campaign}', [CampaignController::class, 'show'])->name('show')->middleware($perm(ModulePermission::CAMPAIGNS, 'view'));
            Route::get('{campaign}/edit', [CampaignController::class, 'edit'])->name('edit')->middleware($perm(ModulePermission::CAMPAIGNS, 'edit'));
            Route::put('{campaign}', [CampaignController::class, 'update'])->name('update')->middleware($perm(ModulePermission::CAMPAIGNS, 'edit'));
            Route::delete('{campaign}', [CampaignController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::CAMPAIGNS, 'delete'));

            Route::get('{campaign}/expenses', [CampaignExpenseController::class, 'campaignIndex'])->name('expenses.index')->middleware($perm(ModulePermission::CAMPAIGN_EXPENSES, 'view'));
            Route::get('{campaign}/beneficiary-report', [CampaignBeneficiaryReportController::class, 'show'])->name('beneficiary-report')->middleware($perm(ModulePermission::CAMPAIGN_BENEFICIARY_REPORTS, 'view'));
            Route::get('{campaign}/beneficiary-supports/create', [BeneficiarySupportController::class, 'createFromCampaign'])->name('beneficiary-supports.create')->middleware($perm(ModulePermission::BENEFICIARY_SUPPORTS, 'create'));
        });

        // ==========================================================
        // Campaign Expenses
        // ==========================================================
        Route::prefix('campaign-expenses')->name('campaign-expenses.')->group(function () use ($perm) {
            Route::get('/', [CampaignExpenseController::class, 'index'])->name('index')->middleware($perm(ModulePermission::CAMPAIGN_EXPENSES, 'view'));
            Route::post('/', [CampaignExpenseController::class, 'store'])->name('store')->middleware($perm(ModulePermission::CAMPAIGN_EXPENSES, 'create'));
            Route::patch('{expense}', [CampaignExpenseController::class, 'update'])->name('update')->middleware($perm(ModulePermission::CAMPAIGN_EXPENSES, 'edit'));
        });

        // ==========================================================
        // Meetings
        // ==========================================================
        Route::prefix('meetings')->name('meetings.')->group(function () use ($perm) {
            Route::get('/', [MeetingController::class, 'index'])->name('index')->middleware($perm(ModulePermission::MEETINGS, 'view'));
            Route::get('create', [MeetingController::class, 'create'])->name('create')->middleware($perm(ModulePermission::MEETINGS, 'create'));
            Route::post('/', [MeetingController::class, 'store'])->name('store')->middleware($perm(ModulePermission::MEETINGS, 'create'));
            Route::get('{meeting}', [MeetingController::class, 'show'])->name('show')->middleware($perm(ModulePermission::MEETINGS, 'view'));
            Route::get('{meeting}/edit', [MeetingController::class, 'edit'])->name('edit')->middleware($perm(ModulePermission::MEETINGS, 'edit'));
            Route::put('{meeting}', [MeetingController::class, 'update'])->name('update')->middleware($perm(ModulePermission::MEETINGS, 'edit'));
            Route::delete('{meeting}', [MeetingController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::MEETINGS, 'delete'));

            Route::get('{meeting}/print', [MeetingController::class, 'print'])->name('print')->middleware($perm(ModulePermission::MEETINGS, 'print'));

            Route::post('{meeting}/minutes', [MeetingMinutesController::class, 'store'])->name('minutes.store')->middleware($perm(ModulePermission::MEETING_MINUTES, 'create'));
            Route::put('{meeting}/minutes/{minutes}', [MeetingMinutesController::class, 'update'])->name('minutes.update')->middleware($perm(ModulePermission::MEETING_MINUTES, 'edit'));
            Route::post('{meeting}/minutes/{minutes}/approve', [MeetingMinutesController::class, 'approve'])->name('minutes.approve')->middleware($perm(ModulePermission::MEETING_MINUTES, 'approve'));

            Route::post('{meeting}/decisions/reorder', [MeetingDecisionController::class, 'reorder'])->name('decisions.reorder')->middleware($perm(ModulePermission::MEETING_DECISIONS, 'reorder'));
            Route::post('{meeting}/decisions', [MeetingDecisionController::class, 'store'])->name('decisions.store')->middleware($perm(ModulePermission::MEETING_DECISIONS, 'create'));
            Route::put('{meeting}/decisions/{decision}', [MeetingDecisionController::class, 'update'])->name('decisions.update')->middleware($perm(ModulePermission::MEETING_DECISIONS, 'edit'));
            Route::patch('{meeting}/decisions/{decision}/status', [MeetingDecisionController::class, 'updateStatus'])->name('decisions.updateStatus')->middleware($perm(ModulePermission::MEETING_DECISIONS, 'update_status'));
            Route::delete('{meeting}/decisions/{decision}', [MeetingDecisionController::class, 'destroy'])->name('decisions.destroy')->middleware($perm(ModulePermission::MEETING_DECISIONS, 'delete'));

            Route::post('{meeting}/attachments', [MeetingAttachmentController::class, 'store'])->name('attachments.store')->middleware($perm(ModulePermission::MEETING_ATTACHMENTS, 'create'));
            Route::get('{meeting}/attachments/{attachment}/download', [MeetingAttachmentController::class, 'download'])->name('attachments.download')->middleware($perm(ModulePermission::MEETING_ATTACHMENTS, 'download'));
            Route::delete('{meeting}/attachments/{attachment}', [MeetingAttachmentController::class, 'destroy'])->name('attachments.destroy')->middleware($perm(ModulePermission::MEETING_ATTACHMENTS, 'delete'));
        });

        // ==========================================================
        // Aid Items
        // ==========================================================
        Route::prefix('aid-items')->name('aid-items.')->group(function () use ($perm) {
            Route::get('/', [AidItemController::class, 'index'])->name('index')->middleware($perm(ModulePermission::AID_ITEMS, 'view'));
            Route::post('/', [AidItemController::class, 'store'])->name('store')->middleware($perm(ModulePermission::AID_ITEMS, 'create'));
            Route::patch('{aidItem}', [AidItemController::class, 'update'])->name('update')->middleware($perm(ModulePermission::AID_ITEMS, 'edit'));
        });

        // ==========================================================
        // Beneficiary Supports
        // ==========================================================
        Route::prefix('beneficiary-supports')->name('beneficiary-supports.')->group(function () use ($perm) {
            Route::post('/', [BeneficiarySupportController::class, 'store'])->name('store')->middleware($perm(ModulePermission::BENEFICIARY_SUPPORTS, 'create'));
        });

        // ==========================================================
        // Donations
        // ==========================================================
        Route::prefix('donations')->name('donations.')->group(function () use ($perm) {
            Route::get('/', [DonationController::class, 'index'])->name('index')->middleware($perm(ModulePermission::DONATIONS, 'view'));
            Route::get('export', [DonationController::class, 'export'])->name('export')->middleware($perm(ModulePermission::DONATIONS, 'export'));
        });

        // ==========================================================
        // Transactions
        // ==========================================================
        Route::prefix('transactions')->name('transactions.')->group(function () use ($perm) {
            Route::get('/', [TransactionController::class, 'index'])->name('index')->middleware($perm(ModulePermission::TRANSACTIONS, 'view'));
            Route::get('create', [TransactionController::class, 'create'])->name('create')->middleware($perm(ModulePermission::TRANSACTIONS, 'create'));
            Route::get('export', [TransactionController::class, 'export'])->name('export')->middleware($perm(ModulePermission::TRANSACTIONS, 'export'));
            Route::post('/', [TransactionController::class, 'store'])->name('store')->middleware($perm(ModulePermission::TRANSACTIONS, 'create'));
            Route::get('{transaction}/edit', [TransactionController::class, 'edit'])->name('edit')->middleware($perm(ModulePermission::TRANSACTIONS, 'edit'));
            Route::get('{transaction}', [TransactionController::class, 'show'])->name('show')->middleware($perm(ModulePermission::TRANSACTIONS, 'view'));
            Route::put('{transaction}', [TransactionController::class, 'update'])->name('update')->middleware($perm(ModulePermission::TRANSACTIONS, 'edit'));
            Route::post('{transaction}/reverse', [TransactionController::class, 'reverse'])->name('reverse')->middleware($perm(ModulePermission::TRANSACTIONS, 'reverse'));
        });

        // ==========================================================
        // Payment Methods
        // ==========================================================
        Route::prefix('payment-methods')->name('payment-methods.')->group(function () use ($perm) {
            Route::get('/', [PaymentMethodController::class, 'index'])->name('index')->middleware($perm(ModulePermission::PAYMENT_METHODS, 'view'));
            Route::post('/', [PaymentMethodController::class, 'store'])->name('store')->middleware($perm(ModulePermission::PAYMENT_METHODS, 'create'));
            Route::put('{payment_method}', [PaymentMethodController::class, 'update'])->name('update')->middleware($perm(ModulePermission::PAYMENT_METHODS, 'edit'));
            Route::delete('{payment_method}', [PaymentMethodController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::PAYMENT_METHODS, 'delete'));
            Route::post('bulk-destroy', [PaymentMethodController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::PAYMENT_METHODS, 'delete'));
            Route::post('{id}/restore', [PaymentMethodController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::PAYMENT_METHODS, 'delete'));
        });

        // ==========================================================
        // General Expense Categories
        // ==========================================================
        Route::prefix('general-expense-categories')->name('general-expense-categories.')->group(function () use ($perm) {
            Route::get('/', [GeneralExpenseCategoryController::class, 'index'])->name('index')->middleware($perm(ModulePermission::GENERAL_EXPENSE_CATEGORIES, 'view'));
            Route::post('/', [GeneralExpenseCategoryController::class, 'store'])->name('store')->middleware($perm(ModulePermission::GENERAL_EXPENSE_CATEGORIES, 'create'));
            Route::put('{general_expense_category}', [GeneralExpenseCategoryController::class, 'update'])->name('update')->middleware($perm(ModulePermission::GENERAL_EXPENSE_CATEGORIES, 'edit'));
            Route::delete('{general_expense_category}', [GeneralExpenseCategoryController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::GENERAL_EXPENSE_CATEGORIES, 'delete'));
            Route::post('bulk-destroy', [GeneralExpenseCategoryController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::GENERAL_EXPENSE_CATEGORIES, 'delete'));
            Route::post('{id}/restore', [GeneralExpenseCategoryController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::GENERAL_EXPENSE_CATEGORIES, 'delete'));
        });

        // ==========================================================
        // General Expenses
        // ==========================================================
        Route::prefix('general-expenses')->name('general-expenses.')->group(function () use ($perm) {
            Route::get('/', [GeneralExpenseController::class, 'index'])->name('index')->middleware($perm(ModulePermission::GENERAL_EXPENSES, 'view'));
            Route::post('/', [GeneralExpenseController::class, 'store'])->name('store')->middleware($perm(ModulePermission::GENERAL_EXPENSES, 'create'));
            Route::patch('{generalExpense}', [GeneralExpenseController::class, 'update'])->name('update')->middleware($perm(ModulePermission::GENERAL_EXPENSES, 'edit'));
            Route::delete('{generalExpense}', [GeneralExpenseController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::GENERAL_EXPENSES, 'delete'));
        });

        // ==========================================================
        // Donor Profiles
        // ==========================================================
        Route::prefix('donor-profiles')->name('donor-profiles.')->group(function () use ($perm) {
            Route::get('/', [DonorProfileController::class, 'index'])->name('index')->middleware($perm(ModulePermission::DONOR_PROFILES, 'view'));
            Route::post('/', [DonorProfileController::class, 'store'])->name('store')->middleware($perm(ModulePermission::DONOR_PROFILES, 'create'));
            Route::get('{donor_profile}', [DonorProfileController::class, 'show'])->name('show')->middleware($perm(ModulePermission::DONOR_PROFILES, 'view'));
            Route::put('{donor_profile}', [DonorProfileController::class, 'update'])->name('update')->middleware($perm(ModulePermission::DONOR_PROFILES, 'edit'));
            Route::delete('{donor_profile}', [DonorProfileController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::DONOR_PROFILES, 'delete'));
            Route::post('{id}/restore', [DonorProfileController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::DONOR_PROFILES, 'delete'));
        });

        // ==========================================================
        // Accounts
        // ==========================================================
        Route::prefix('accounts')->name('accounts.')->group(function () use ($perm) {
            Route::get('/', [BankAccountController::class, 'index'])->name('index')->middleware($perm(ModulePermission::ACCOUNTS, 'view'));
            Route::post('/', [BankAccountController::class, 'store'])->name('store')->middleware($perm(ModulePermission::ACCOUNTS, 'create'));
            Route::post('bulk-destroy', [BankAccountController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::ACCOUNTS, 'delete'));
            Route::put('{account}', [BankAccountController::class, 'update'])->name('update')->middleware($perm(ModulePermission::ACCOUNTS, 'edit'));
            Route::delete('{account}', [BankAccountController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::ACCOUNTS, 'delete'));
            Route::post('{id}/restore', [BankAccountController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::ACCOUNTS, 'delete'));
        });

        // ==========================================================
        // FAQs
        // ==========================================================
        Route::prefix('faqs')->name('faqs.')->group(function () use ($perm) {
            Route::get('/', [FaqController::class, 'index'])->name('index')->middleware($perm(ModulePermission::FAQS, 'view'));
            Route::post('/', [FaqController::class, 'store'])->name('store')->middleware($perm(ModulePermission::FAQS, 'create'));
            Route::put('{faq}', [FaqController::class, 'update'])->name('update')->middleware($perm(ModulePermission::FAQS, 'edit'));
            Route::delete('{faq}', [FaqController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::FAQS, 'delete'));
            Route::post('bulk-destroy', [FaqController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::FAQS, 'delete'));
            Route::post('{id}/restore', [FaqController::class, 'restore'])->name('restore')->middleware($perm(ModulePermission::FAQS, 'delete'));
        });

        // ==========================================================
        // Legal Documents
        // ==========================================================
        Route::prefix('legal')->name('legal.')->group(function () use ($perm) {
            Route::get('terms', [LegalDocumentController::class, 'editTerms'])->name('terms.edit')->middleware($perm(ModulePermission::LEGAL_DOCUMENTS, 'view'));
            Route::patch('terms', [LegalDocumentController::class, 'updateTerms'])->name('terms.update')->middleware($perm(ModulePermission::LEGAL_DOCUMENTS, 'edit'));
            Route::get('privacy', [LegalDocumentController::class, 'editPrivacy'])->name('privacy.edit')->middleware($perm(ModulePermission::LEGAL_DOCUMENTS, 'view'));
            Route::patch('privacy', [LegalDocumentController::class, 'updatePrivacy'])->name('privacy.update')->middleware($perm(ModulePermission::LEGAL_DOCUMENTS, 'edit'));
        });

        // ==========================================================
        // Contact Messages
        // ==========================================================
        Route::prefix('contact-messages')->name('contact-messages.')->group(function () use ($perm) {
            Route::get('/', [ContactMessageController::class, 'index'])->name('index')->middleware($perm(ModulePermission::CONTACT_MESSAGES, 'view'));
            Route::get('{contactMessage}', [ContactMessageController::class, 'show'])->name('show')->middleware($perm(ModulePermission::CONTACT_MESSAGES, 'view'));
            Route::delete('{contactMessage}', [ContactMessageController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::CONTACT_MESSAGES, 'delete'));
            Route::post('bulk-destroy', [ContactMessageController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::CONTACT_MESSAGES, 'delete'));
            Route::patch('{contactMessage}/mark-reviewed', [ContactMessageController::class, 'markReviewed'])->name('mark-reviewed')->middleware($perm(ModulePermission::CONTACT_MESSAGES, 'mark_reviewed'));
        });

        // ==========================================================
        // Beneficiaries
        // ==========================================================
        Route::prefix('beneficiaries')->name('beneficiaries.')->group(function () use ($perm) {
            Route::get('/', [BeneficiaryController::class, 'index'])->name('index')->middleware($perm(ModulePermission::BENEFICIARIES, 'view'));
            Route::get('create', [BeneficiaryController::class, 'create'])->name('create')->middleware($perm(ModulePermission::BENEFICIARIES, 'create'));
            Route::post('/', [BeneficiaryController::class, 'store'])->name('store')->middleware($perm(ModulePermission::BENEFICIARIES, 'create'));
            Route::get('{beneficiary}', [BeneficiaryController::class, 'show'])->name('show')->middleware($perm(ModulePermission::BENEFICIARIES, 'view'));
            Route::get('{beneficiary}/edit', [BeneficiaryController::class, 'edit'])->name('edit')->middleware($perm(ModulePermission::BENEFICIARIES, 'edit'));
            Route::put('{beneficiary}', [BeneficiaryController::class, 'update'])->name('update')->middleware($perm(ModulePermission::BENEFICIARIES, 'edit'));
            Route::delete('{beneficiary}', [BeneficiaryController::class, 'destroy'])->name('destroy')->middleware($perm(ModulePermission::BENEFICIARIES, 'delete'));

            Route::patch('{beneficiary}/status', [BeneficiaryController::class, 'updateStatus'])->name('status')->middleware($perm(ModulePermission::BENEFICIARIES, 'update_status'));
            Route::get('{beneficiary}/beneficiary-supports/create', [BeneficiarySupportController::class, 'createFromBeneficiary'])->name('beneficiary-supports.create')->middleware($perm(ModulePermission::BENEFICIARY_SUPPORTS, 'create'));
            Route::get('{beneficiary}/support-report', [BeneficiarySupportReportController::class, 'show'])->name('support-report')->middleware($perm(ModulePermission::BENEFICIARY_SUPPORT_REPORTS, 'view'));
            Route::post('bulk-destroy', [BeneficiaryController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware($perm(ModulePermission::BENEFICIARIES, 'delete'));

            // Beneficiary Assessments (nested)
            Route::post('{beneficiary}/assessments', [BeneficiaryAssessmentController::class, 'store'])->name('assessments.store')->middleware($perm(ModulePermission::BENEFICIARY_ASSESSMENTS, 'create'));
            Route::put('{beneficiary}/assessments/{assessment}', [BeneficiaryAssessmentController::class, 'update'])->name('assessments.update')->middleware($perm(ModulePermission::BENEFICIARY_ASSESSMENTS, 'edit'));
        });
    });
});

require __DIR__.'/settings.php';
