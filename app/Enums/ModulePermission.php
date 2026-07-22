<?php

namespace App\Enums;

enum ModulePermission: string
{
    case SYSTEM = 'system';

    // ─── Users & Access Control ───────────────────────────────────────────
    case USERS = 'users';
    case ROLES = 'roles';

    // ─── CMS ───────────────────────────────────────────────────────────────
    case NEWS = 'news';
    case NEWS_CATEGORIES = 'news_categories';
    case FAQS = 'faqs';
    case LEGAL_DOCUMENTS = 'legal_documents';
    case CONTACT_MESSAGES = 'contact_messages';

    // ─── Campaigns ───────────────────────────────────────────────────────
    case CAMPAIGN_CATEGORIES = 'campaign_categories';
    case CAMPAIGNS = 'campaigns';
    case CAMPAIGN_EXPENSES = 'campaign_expenses';
    case CAMPAIGN_BENEFICIARY_REPORTS = 'campaign_beneficiary_reports';

    // ─── Meetings ────────────────────────────────────────────────────────
    case MEETINGS = 'meetings';
    case MEETING_MINUTES = 'meeting_minutes';
    case MEETING_DECISIONS = 'meeting_decisions';
    case MEETING_ATTACHMENTS = 'meeting_attachments';

    // ─── Beneficiaries ───────────────────────────────────────────────────
    case BENEFICIARIES = 'beneficiaries';
    case BENEFICIARY_ASSESSMENTS = 'beneficiary_assessments';
    case BENEFICIARY_SUPPORTS = 'beneficiary_supports';
    case BENEFICIARY_SUPPORT_REPORTS = 'beneficiary_support_reports';
    case AID_ITEMS = 'aid_items';

    // ─── Financial ───────────────────────────────────────────────────────
    case DONATIONS = 'donations';
    case TRANSACTIONS = 'transactions';
    case TRANSFERS = 'transfers';
    case ACCOUNTS = 'accounts';
    case PAYMENT_METHODS = 'payment_methods';
    case GENERAL_EXPENSES = 'general_expenses';
    case GENERAL_EXPENSE_CATEGORIES = 'general_expense_categories';
    case DONOR_PROFILES = 'donor_profiles';

    /**
     * Generate a uniform permission string for the given ability.
     */
    public function permission(string $ability): string
    {
        if ($this === self::SYSTEM) {
            return $ability;
        }

        return "{$ability}_{$this->value}";
    }

    /**
     * Get the valid abilities for this module, falling back to the
     * application's default abilities when no override is configured.
     *
     * @return array<int, string>
     */
    public function abilities(): array
    {
        $modules = config('permissions.modules', []);
        $defaults = config('permissions.default_abilities', []);

        return $modules[$this->value] ?? $defaults;
    }

    /**
     * Generate all permission variations for this module.
     *
     * @return array<int, string>
     */
    public function allPermissions(): array
    {
        return array_map(
            fn (string $ability) => $this->permission($ability),
            $this->abilities()
        );
    }
}
