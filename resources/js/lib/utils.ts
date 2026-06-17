import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Format an integer amount in minor units (piasters/cents) as Egyptian Pounds.
 * The DB stores floats; the API layer converts to integer cents before sending props.
 */
export function formatCurrency(minorUnits: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(minorUnits / 100);
}

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function getPageNumbers(currentPage: number, totalPages: number) {
    const maxVisiblePages = 5
    const rangeWithDots: (number | string)[] = []

    if (totalPages <= maxVisiblePages) {
        for (let i = 1; i <= totalPages; i++) {
            rangeWithDots.push(i)
        }
    } else {
        rangeWithDots.push(1)

        if (currentPage <= 3) {
            for (let i = 2; i <= 4; i++) {
                rangeWithDots.push(i)
            }
            rangeWithDots.push('...', totalPages)
        } else if (currentPage >= totalPages - 2) {
            rangeWithDots.push('...')
            for (let i = totalPages - 3; i <= totalPages; i++) {
                rangeWithDots.push(i)
            }
        } else {
            rangeWithDots.push('...')
            for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                rangeWithDots.push(i)
            }
            rangeWithDots.push('...', totalPages)
        }
    }

    return rangeWithDots
}
