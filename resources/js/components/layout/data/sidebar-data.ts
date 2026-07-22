import {
  ArrowLeftRight,
  CalendarDays,
  FileText,
  FolderKanban,
  HelpCircle,
  LayoutDashboard,
  Mail,
  Megaphone,
  Newspaper,
  CreditCard,
  HeartHandshake,
  Layers,
  Receipt,
  Scale,
  Shield,
  Tags,
  UserRound,
  Users,
  Wallet,
} from 'lucide-react'
import { index as accountsIndex } from '@/routes/admin/accounts'
import { index as beneficiariesIndex } from '@/routes/admin/beneficiaries'
import { index as donorProfilesIndex } from '@/routes/admin/donor-profiles'
import { index as campaignExpensesIndex } from '@/routes/admin/campaign-expenses'
import { index as campaignsIndex } from '@/routes/admin/campaigns'
import { index as contactMessagesIndex } from '@/routes/admin/contact-messages'
import { index as faqsIndex } from '@/routes/admin/faqs'
import { index as generalExpenseCategoriesIndex } from '@/routes/admin/general-expense-categories'
import { index as generalExpensesIndex } from '@/routes/admin/general-expenses'
import { index as paymentMethodsIndex } from '@/routes/admin/payment-methods'
import { index as donationsIndex } from '@/routes/admin/donations'
import { index as transactionsIndex } from '@/routes/admin/transactions'
import { edit as legalPrivacyEdit } from '@/routes/admin/legal/privacy'
import { edit as legalTermsEdit } from '@/routes/admin/legal/terms'
import { type SidebarData } from '@/types/nav-types';

export const sidebarData: SidebarData = {
  user: {
    name: 'satnaing',
    email: 'satnaingdev@gmail.com',
    avatar: '/avatars/shadcn.jpg',
  },
  teams: [
    {
      name: 'Shadcn Admin',
      logo: LayoutDashboard,
      plan: 'Vite + ShadcnUI',
    },
  ],
  navGroups: [
    {
      title: 'General',
      items: [
        {
          title: 'Dashboard',
          url: '/admin/dashboard',
          icon: LayoutDashboard,
        },
        {
          title: 'Users',
          url: '/admin/users',
          icon: Users,
          permission: 'view_users',
        },
        {
          title: 'News',
          url: '/admin/news',
          icon: Newspaper,
          permission: 'view_news',
        },
        {
          title: 'News Categories',
          url: '/admin/news-categories',
          icon: Tags,
          permission: 'view_news',
        },
        {
          title: 'Campaign Categories',
          url: '/admin/campaign-categories',
          icon: FolderKanban,
          permission: 'view_campaign_categories',
        },
        {
          title: 'Roles',
          url: '/admin/roles',
          icon: Shield,
          permission: 'view_roles',
        },
        {
          title: 'FAQs',
          url: faqsIndex.url(),
          icon: HelpCircle,
          permission: 'view_faqs',
        },
        {
          title: 'Terms & Conditions',
          url: legalTermsEdit.url(),
          icon: Scale,
          permission: 'view_legal_documents',
        },
        {
          title: 'Privacy Policy',
          url: legalPrivacyEdit.url(),
          icon: FileText,
          permission: 'view_legal_documents',
        },
        {
          title: 'Contact Messages',
          url: contactMessagesIndex.url(),
          icon: Mail,
          permission: 'view_contact_messages',
        },
      ],
    },
    {
      title: 'Beneficiaries',
      items: [
        {
          title: 'Beneficiaries',
          url: beneficiariesIndex.url(),
          icon: UserRound,
          permission: 'view_beneficiaries',
        },
        {
          title: 'Donor Profiles',
          url: donorProfilesIndex.url(),
          icon: HeartHandshake,
          permission: 'view_donor_profiles',
        },
        {
          title: 'Aid Items',
          url: '/admin/aid-items',
          icon: Layers,
        },
      ],
    },
    {
      title: 'Finance',
      items: [
        {
          title: 'Bank Accounts',
          url: accountsIndex.url(),
          icon: Wallet,
          permission: 'view_accounts',
        },
        {
          title: 'Payment Methods',
          url: paymentMethodsIndex.url(),
          icon: CreditCard,
          permission: 'view_payment_methods',
        },
        {
          title: 'Expense Categories',
          url: generalExpenseCategoriesIndex.url(),
          icon: Layers,
          permission: 'view_general_expense_categories',
        },
        {
          title: 'General Expenses',
          url: generalExpensesIndex.url(),
          icon: Receipt,
          permission: 'view_general_expenses',
        },
        {
          title: 'Campaigns',
          url: campaignsIndex.url(),
          icon: Megaphone,
          permission: 'view_campaigns',
        },
        {
          title: 'Meetings',
          url: '/admin/meetings',
          icon: CalendarDays,
          permission: 'view_meetings',
        },
        {
          title: 'Campaign Expenses',
          url: campaignExpensesIndex.url(),
          icon: Receipt,
          permission: 'view_campaign_expenses',
        },
        {
          title: 'Donations',
          url: donationsIndex.url(),
          icon: HeartHandshake,
          permission: 'view_donations',
        },
        {
          title: 'Transactions',
          url: transactionsIndex.url(),
          icon: ArrowLeftRight,
          permission: 'view_transactions',
        },
      ],
    },
  ],
}
