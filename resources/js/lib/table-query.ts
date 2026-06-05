type SearchRecord = Record<string, unknown>

export function parseSearchNumber(value: unknown, fallback: number): number {
  if (typeof value === 'number' && !Number.isNaN(value)) {
    return value
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Number(value)

    if (!Number.isNaN(parsed)) {
      return parsed
    }
  }

  return fallback
}

export function buildTableQueryParams(
  search: SearchRecord,
  patch: SearchRecord,
  defaults: { page?: number; perPage?: number } = {}
): Record<string, string | number | string[]> {
  const defaultPage = defaults.page ?? 1
  const defaultPerPage = defaults.perPage ?? 25
  const merged = { ...search, ...patch }
  const params: Record<string, string | number | string[]> = {}

  for (const [key, value] of Object.entries(merged)) {
    if (value === undefined || value === null || value === '') {
      continue
    }

    params[key] = value as string | number | string[]
  }

  if (parseSearchNumber(params.page, defaultPage) <= defaultPage) {
    delete params.page
  }

  if (parseSearchNumber(params.per_page, defaultPerPage) === defaultPerPage) {
    delete params.per_page
  }

  return params
}
