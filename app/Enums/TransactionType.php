<?php

namespace App\Enums;

enum TransactionType: string
{
    case Donation        = 'donation';
    case CampaignExpense = 'campaign_expense';
    case GeneralExpense  = 'general_expense';
    case Transfer        = 'transfer';
    case BankTransfer    = 'bank_transfer';
    case Adjustment      = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::Donation        => 'Donation',
            self::CampaignExpense => 'Campaign Expense',
            self::GeneralExpense  => 'General Expense',
            self::Transfer        => 'Transfer',
            self::BankTransfer    => 'Bank Transfer',
            self::Adjustment      => 'Adjustment',
        };
    }

    public function isIncome(): bool
    {
        return $this === self::Donation;
    }

    public function isExpense(): bool
    {
        return in_array($this, [
            self::CampaignExpense,
            self::GeneralExpense,
            self::Transfer,
            self::BankTransfer,
        ]);
    }
}
