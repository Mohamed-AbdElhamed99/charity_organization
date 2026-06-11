import type {
  GeneralExpense,
  GeneralExpenseAccountOption,
  GeneralExpenseCategoryOption,
  GeneralExpensePaymentMethodOption,
} from '@/types/models/general-expense'

export function getGeneralExpenseDisplayName(expense: GeneralExpense): string {
  return expense.name
}

export function selectOptionsFromCategories(
  categories: GeneralExpenseCategoryOption[]
) {
  return categories.map((category) => ({
    label: category.name,
    value: String(category.id),
  }))
}

export function selectOptionsFromAccounts(
  accounts: GeneralExpenseAccountOption[]
) {
  return accounts.map((account) => ({
    label: account.name,
    value: String(account.id),
  }))
}

export function selectOptionsFromPaymentMethods(
  paymentMethods: GeneralExpensePaymentMethodOption[]
) {
  return paymentMethods.map((method) => ({
    label: method.name,
    value: String(method.id),
  }))
}

function todayDateInputValue(): string {
  return new Date().toISOString().slice(0, 10)
}

export function defaultGeneralExpenseFormValues() {
  return {
    account_id: '',
    name: '',
    amount: '',
    expense_date: todayDateInputValue(),
    category_id: '',
    payment_method_id: '',
    vendor_name: '',
    is_recurring: false,
    description: '',
    notes: '',
    reference_number: '',
  }
}

export function defaultEditGeneralExpenseFormValues(expense: GeneralExpense) {
  return {
    category_id: expense.category_id != null ? String(expense.category_id) : '',
    name: expense.name,
    vendor_name: expense.vendor_name ?? '',
    is_recurring: expense.is_recurring,
    notes: expense.notes ?? '',
  }
}
