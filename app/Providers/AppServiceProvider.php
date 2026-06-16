<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\Services\AccountServiceInterface;
use App\Contracts\Services\AssessmentServiceInterface;
use App\Contracts\Services\BeneficiaryServiceInterface;
use App\Contracts\Services\CampaignCategoryServiceInterface;
use App\Contracts\Services\CampaignExpenseServiceInterface;
use App\Contracts\Services\CampaignServiceInterface;
use App\Contracts\Services\ContactMessageServiceInterface;
use App\Contracts\Services\DonorProfileServiceInterface;
use App\Contracts\Services\FaqServiceInterface;
use App\Contracts\Services\GeneralExpenseCategoryServiceInterface;
use App\Contracts\Services\GeneralExpenseServiceInterface;
use App\Contracts\Services\LegalDocumentServiceInterface;
use App\Contracts\Services\NewsCategoryServiceInterface;
use App\Contracts\Services\NewsServiceInterface;
use App\Contracts\Services\PaymentMethodServiceInterface;
use App\Contracts\Services\RoleServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Contracts\Services\TransferServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Services\AccountService;
use App\Services\AssessmentService;
use App\Services\BeneficiaryService;
use App\Services\CampaignCategoryService;
use App\Services\CampaignExpenseService;
use App\Services\CampaignService;
use App\Services\ContactMessageService;
use App\Services\DonorProfileService;
use App\Services\FaqService;
use App\Services\GeneralExpenseCategoryService;
use App\Services\GeneralExpenseService;
use App\Services\LegalDocumentService;
use App\Services\NewsCategoryService;
use App\Services\NewsService;
use App\Services\PaymentMethodService;
use App\Services\Payments\StripeGateway;
use App\Services\RoleService;
use App\Services\TransactionService;
use App\Services\TransferService;
use App\Services\UserService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            $secret = (string) config('services.stripe.secret', '');

            if (blank($secret)) {
                throw new InvalidArgumentException('Stripe secret key is not configured. Set STRIPE_SECRET in your environment.');
            }

            return new StripeClient($secret);
        });

        $this->app->bind(PaymentGateway::class, StripeGateway::class);
        $this->app->bind(AccountServiceInterface::class, AccountService::class);
        $this->app->bind(BeneficiaryServiceInterface::class, BeneficiaryService::class);
        $this->app->bind(AssessmentServiceInterface::class, AssessmentService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(NewsServiceInterface::class, NewsService::class);
        $this->app->bind(NewsCategoryServiceInterface::class, NewsCategoryService::class);
        $this->app->bind(CampaignCategoryServiceInterface::class, CampaignCategoryService::class);
        $this->app->bind(CampaignServiceInterface::class, CampaignService::class);
        $this->app->bind(CampaignExpenseServiceInterface::class, CampaignExpenseService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(TransferServiceInterface::class, TransferService::class);
        $this->app->bind(FaqServiceInterface::class, FaqService::class);
        $this->app->bind(LegalDocumentServiceInterface::class, LegalDocumentService::class);
        $this->app->bind(ContactMessageServiceInterface::class, ContactMessageService::class);
        $this->app->bind(PaymentMethodServiceInterface::class, PaymentMethodService::class);
        $this->app->bind(GeneralExpenseCategoryServiceInterface::class, GeneralExpenseCategoryService::class);
        $this->app->bind(GeneralExpenseServiceInterface::class, GeneralExpenseService::class);
        $this->app->bind(DonorProfileServiceInterface::class, DonorProfileService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
