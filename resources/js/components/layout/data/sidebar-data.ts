import {
  ArrowLeftRight,
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
  Send,
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
import { index as transactionsIndex } from '@/routes/admin/transactions'
import { index as transfersIndex } from '@/routes/admin/transfers'
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
        },
        {
          title: 'News',
          url: '/admin/news',
          icon: Newspaper,
        },
        {
          title: 'News Categories',
          url: '/admin/news-categories',
          icon: Tags,
        },
        {
          title: 'Campaign Categories',
          url: '/admin/campaign-categories',
          icon: FolderKanban,
        },
        {
          title: 'Roles',
          url: '/admin/roles',
          icon: Shield,
        },
        {
          title: 'FAQs',
          url: faqsIndex.url(),
          icon: HelpCircle,
        },
        {
          title: 'Terms & Conditions',
          url: legalTermsEdit.url(),
          icon: Scale,
        },
        {
          title: 'Privacy Policy',
          url: legalPrivacyEdit.url(),
          icon: FileText,
        },
        {
          title: 'Contact Messages',
          url: contactMessagesIndex.url(),
          icon: Mail,
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
        },
        {
          title: 'Donor Profiles',
          url: donorProfilesIndex.url(),
          icon: HeartHandshake,
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
        },
        {
          title: 'Payment Methods',
          url: paymentMethodsIndex.url(),
          icon: CreditCard,
        },
        {
          title: 'Expense Categories',
          url: generalExpenseCategoriesIndex.url(),
          icon: Layers,
        },
        {
          title: 'General Expenses',
          url: generalExpensesIndex.url(),
          icon: Receipt,
        },
        {
          title: 'Campaigns',
          url: campaignsIndex.url(),
          icon: Megaphone,
        },
        {
          title: 'Campaign Expenses',
          url: campaignExpensesIndex.url(),
          icon: Receipt,
        },
        {
          title: 'Transactions',
          url: transactionsIndex.url(),
          icon: ArrowLeftRight,
        },
        {
          title: 'Transfers',
          url: transfersIndex.url(),
          icon: Send,
        },
      ],
    },
  ],
}
