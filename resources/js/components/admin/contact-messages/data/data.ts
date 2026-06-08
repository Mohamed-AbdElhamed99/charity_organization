export const statusOptions = [
  { label: 'Reviewed', value: 'reviewed' },
  { label: 'Unreviewed', value: 'unreviewed' },
] as const

export const callTypes = new Map<boolean, string>([
  [true, 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  [false, 'bg-amber-100/30 text-amber-900 dark:text-amber-200 border-amber-200'],
])
