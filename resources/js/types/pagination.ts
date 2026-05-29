import type { LucideIcon } from 'lucide-react'

// ─── Core Laravel paginator shape ───────────────────────────────────────────

export interface PaginatedLink {
  url: string | null
  label: string
  active: boolean
}

export interface Paginated<T> {
  data: T[]

  // page info
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null   // first item index on this page (null if empty)
  to: number | null     // last item index on this page  (null if empty)

  // urls
  first_page_url: string
  last_page_url: string
  next_page_url: string | null
  prev_page_url: string | null
  path: string

  // links array Laravel includes (useful for rendering page buttons)
  links: PaginatedLink[]
}

// ─── Simple paginator (no total count) ──────────────────────────────────────
// Used when you call ->simplePaginate() on the backend

export interface SimplePaginated<T> {
  data: T[]
  per_page: number
  from: number | null
  to: number | null
  first_page_url: string
  next_page_url: string | null
  prev_page_url: string | null
  path: string
}

// ─── Cursor paginator ────────────────────────────────────────────────────────
// Used when you call ->cursorPaginate() on the backend

export interface CursorPaginated<T> {
  data: T[]
  per_page: number
  next_cursor: string | null
  prev_cursor: string | null
  next_page_url: string | null
  prev_page_url: string | null
  path: string
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** True if there are more pages beyond the current one */
export function hasMorePages(page: Paginated<unknown>): boolean {
  return page.current_page < page.last_page
}

/** Returns page numbers to render (with null = ellipsis gap) */
export function pageRange(
  current: number,
  last: number,
  delta = 2
): (number | null)[] {
  const range: number[] = []

  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    range.push(i)
  }

  const result: (number | null)[] = []

  if (range[0] > 1) {
    result.push(1)
    if (range[0] > 2) result.push(null) // ellipsis
  }

  result.push(...range)

  if (range[range.length - 1] < last) {
    if (range[range.length - 1] < last - 1) result.push(null) // ellipsis
    result.push(last)
  }

  return result
}