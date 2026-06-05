import { describe, expect, it } from 'vitest'
import { buildTableQueryParams, parseSearchNumber } from '@/lib/table-query'

describe('table-query', () => {
  it('parses numeric strings', () => {
    expect(parseSearchNumber('2', 1)).toBe(2)
    expect(parseSearchNumber(3, 1)).toBe(3)
    expect(parseSearchNumber('', 1)).toBe(1)
    expect(parseSearchNumber(undefined, 25)).toBe(25)
  })

  it('builds query params and omits default page and per_page', () => {
    expect(
      buildTableQueryParams(
        { query: 'john', page: '2', per_page: '25', role: ['admin'] },
        { page: 1 },
        { page: 1, perPage: 25 }
      )
    ).toEqual({
      query: 'john',
      role: ['admin'],
    })
  })

  it('merges patch values into existing search params', () => {
    expect(
      buildTableQueryParams(
        { query: 'john', role: ['admin'], page: '2' },
        { page: 3 },
        { page: 1, perPage: 25 }
      )
    ).toEqual({
      query: 'john',
      role: ['admin'],
      page: 3,
    })
  })
})
