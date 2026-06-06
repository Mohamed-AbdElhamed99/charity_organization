import { LayoutDashboard, Newspaper, Users } from 'lucide-react'
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
      ],
    },
  ],
}
