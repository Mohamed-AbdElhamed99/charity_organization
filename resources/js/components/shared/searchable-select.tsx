import { useState } from 'react'
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

type OptionAccessors<T> =
  | {
      valueKey: keyof T & string
      labelKey: keyof T & string
      getOptionValue?: never
      getOptionLabel?: never
    }
  | {
      valueKey?: never
      labelKey?: never
      getOptionValue: (option: T) => string
      getOptionLabel: (option: T) => string
    }

type SearchableSelectBaseProps<T> = {
  options: T[]
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  noneAvailableText?: string
  clearLabel?: string
  error?: string
  disabled?: boolean
  className?: string
} & OptionAccessors<T>

type SearchableSelectProps<T> =
  | (SearchableSelectBaseProps<T> & {
      mode?: 'multiple'
      value: string[]
      onChange: (value: string[]) => void
    })
  | (SearchableSelectBaseProps<T> & {
      mode: 'single'
      value: string | null
      onChange: (value: string | null) => void
    })

function resolveOptionValue<T>(
  option: T,
  props: OptionAccessors<T>,
): string {
  if (props.getOptionValue) {
    return props.getOptionValue(option)
  }

  return String(option[props.valueKey])
}

function resolveOptionLabel<T>(
  option: T,
  props: OptionAccessors<T>,
): string {
  if (props.getOptionLabel) {
    return props.getOptionLabel(option)
  }

  return String(option[props.labelKey])
}

export function SearchableSelect<T>(props: SearchableSelectProps<T>) {
  const {
    options,
    placeholder = 'Select…',
    searchPlaceholder = 'Search…',
    emptyText = 'No results found.',
    noneAvailableText = 'No options available.',
    clearLabel = 'Clear selection',
    error,
    disabled = false,
    className,
  } = props

  const isMultiple = props.mode !== 'single'
  const [open, setOpen] = useState(false)

  const selectedValues = new Set(
    isMultiple
      ? props.value
      : props.value
        ? [props.value]
        : [],
  )

  const selectedOptions = options.filter((option) =>
    selectedValues.has(resolveOptionValue(option, props)),
  )

  const triggerLabel = (() => {
    if (selectedValues.size === 0) {
      return placeholder
    }

    if (!isMultiple) {
      return selectedOptions[0]
        ? resolveOptionLabel(selectedOptions[0], props)
        : placeholder
    }

    return `${selectedValues.size} selected`
  })()

  const toggle = (id: string) => {
    if (isMultiple) {
      if (selectedValues.has(id)) {
        props.onChange(props.value.filter((item) => item !== id))
        return
      }

      props.onChange([...props.value, id])
      return
    }

    const next = props.value === id ? null : id
    props.onChange(next)
    setOpen(false)
  }

  const remove = (id: string) => {
    if (isMultiple) {
      props.onChange(props.value.filter((item) => item !== id))
      return
    }

    props.onChange(null)
  }

  const clear = () => {
    if (isMultiple) {
      props.onChange([])
      return
    }

    props.onChange(null)
    setOpen(false)
  }

  return (
    <div className={cn('space-y-3', className)}>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            type="button"
            variant="outline"
            role="combobox"
            aria-expanded={open}
            disabled={disabled}
            className="h-auto min-h-9 w-full justify-between font-normal"
          >
            <span className="truncate text-start">{triggerLabel}</span>
            <ChevronsUpDown className="ms-2 size-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent
          className="w-[var(--radix-popover-trigger-width)] p-0"
          align="start"
        >
          <Command>
            <CommandInput placeholder={searchPlaceholder} />
            <CommandList>
              <CommandEmpty>{emptyText}</CommandEmpty>
              <CommandGroup>
                {options.map((option) => {
                  const optionValue = resolveOptionValue(option, props)
                  const optionLabel = resolveOptionLabel(option, props)
                  const isSelected = selectedValues.has(optionValue)

                  return (
                    <CommandItem
                      key={optionValue}
                      value={optionLabel}
                      onSelect={() => toggle(optionValue)}
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
                      <span>{optionLabel}</span>
                    </CommandItem>
                  )
                })}
              </CommandGroup>
              {selectedValues.size > 0 && (
                <>
                  <CommandSeparator />
                  <CommandGroup>
                    <CommandItem
                      onSelect={clear}
                      className="justify-center text-center"
                    >
                      {clearLabel}
                    </CommandItem>
                  </CommandGroup>
                </>
              )}
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>

      {isMultiple && selectedOptions.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {selectedOptions.map((option) => {
            const optionValue = resolveOptionValue(option, props)
            const optionLabel = resolveOptionLabel(option, props)

            return (
              <Badge
                key={optionValue}
                variant="secondary"
                className="gap-1 pe-1 font-normal"
              >
                {optionLabel}
                <button
                  type="button"
                  className="hover:bg-muted rounded-sm p-0.5"
                  aria-label={`Remove ${optionLabel}`}
                  onClick={() => remove(optionValue)}
                >
                  <X className="size-3" />
                </button>
              </Badge>
            )
          })}
        </div>
      )}

      {options.length === 0 && (
        <p className="text-muted-foreground text-sm">{noneAvailableText}</p>
      )}

      <InputError message={error} />
    </div>
  )
}
