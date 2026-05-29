import { useEffect } from 'react'
import { Monitor, Check, Moon, Sun } from 'lucide-react'
import { cn } from '@/lib/utils'
import { useAppearance, Appearance } from '@/hooks/use-appearance';
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { LucideIcon } from 'lucide-react';

export function ThemeSwitch() {
  const { appearance, resolvedAppearance, updateAppearance } = useAppearance()
  const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
  ];

  /* Update theme-color meta tag
   * when theme is updated */
  useEffect(() => {
    const themeColor = appearance === 'dark' ? '#020817' : '#fff'
    const metaThemeColor = document.querySelector("meta[name='theme-color']")
    if (metaThemeColor) metaThemeColor.setAttribute('content', themeColor)
  }, [appearance])

  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant='ghost' size='icon' className='scale-95 rounded-full'>
          <Sun className='size-[1.2rem] scale-100 rotate-0 transition-all dark:scale-0 dark:-rotate-90' />
          <Moon className='absolute size-[1.2rem] scale-0 rotate-90 transition-all dark:scale-100 dark:rotate-0' />
          <span className='sr-only'>Toggle theme</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align='end'>
        {tabs.map(({ value, icon: Icon, label }) => (
          <DropdownMenuItem
            key={value}
            onClick={() => updateAppearance(value)}
            data-active={appearance === value}
            className="data-[active=true]:bg-accent data-[active=true]:text-accent-foreground"
          >
            {label + ' '}
            <Icon className="-ml-1 h-4 w-4" />
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
