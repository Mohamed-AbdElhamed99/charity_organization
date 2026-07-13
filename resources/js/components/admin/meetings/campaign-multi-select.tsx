import { CheckIcon, ChevronsUpDown, X } from 'lucide-react'
import InputError from '@/components/input-error'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { cn } from '@/lib/utils'
import type { SelectOption } from '@/types/models/meeting'

type CampaignMultiSelectProps = {
  options: SelectOption[]
  value: string[]
  onChange: (ids: string[]) => void
  error?: string
}

export function CampaignMultiSelect({
  options,
  value,
  onChange,
  error,
}: CampaignMultiSelectProps) {
  const selected = new Set(value)

  const toggle = (id: string) => {
    if (selected.has(id)) {
      onChange(value.filter((item) => item !== id))
      return
    }
    onChange([...value, id])
  }

  const remove = (id: string) => {
    onChange(value.filter((item) => item !== id))
  }

  const selectedOptions = options.filter((option) => selected.has(option.value))

  return (
    <div className="space-y-3">
      <Popover>
        <PopoverTrigger asChild>
          <Button
            type="button"
            variant="outline"
            role="combobox"
            className="h-auto min-h-9 w-full justify-between font-normal"
          >
            <span className="truncate text-start">
              {selected.size === 0
                ? 'Select campaigns…'
                : `${selected.size} campaign${selected.size === 1 ? '' : 's'} selected`}
            </span>
            <ChevronsUpDown className="ms-2 size-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
          <Command>
            <CommandInput placeholder="Search campaigns…" />
            <CommandList>
              <CommandEmpty>No campaigns found.</CommandEmpty>
              <CommandGroup>
                {options.map((option) => {
                  const isSelected = selected.has(option.value)
                  return (
                    <CommandItem
                      key={option.value}
                      value={option.label}
                      onSelect={() => toggle(option.value)}
                    >
                      <div
                        className={cn(
                          'flex size-4 items-center justify-center rounded-sm border border-primary',
                          isSelected
                            ? 'bg-primary text-primary-foreground'
                            : 'opacity-50 [&_svg]:invisible',
                        )}
                      >
                        <CheckIcon className="size-3.5 text-background" />
                      </div>
                      <span>{option.label}</span>
                    </CommandItem>
                  )
                })}
              </CommandGroup>
              {selected.size > 0 && (
                <>
                  <CommandSeparator />
                  <CommandGroup>
                    <CommandItem
                      onSelect={() => onChange([])}
                      className="justify-center text-center"
                    >
                      Clear selection
                    </CommandItem>
                  </CommandGroup>
                </>
              )}
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>

      {selectedOptions.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {selectedOptions.map((option) => (
            <Badge
              key={option.value}
              variant="secondary"
              className="gap-1 pe-1 font-normal"
            >
              {option.label}
              <button
                type="button"
                className="hover:bg-muted rounded-sm p-0.5"
                aria-label={`Remove ${option.label}`}
                onClick={() => remove(option.value)}
              >
                <X className="size-3" />
              </button>
            </Badge>
          ))}
        </div>
      )}

      {options.length === 0 && (
        <p className="text-muted-foreground text-sm">No campaigns available.</p>
      )}

      <InputError message={error} />
    </div>
  )
}
