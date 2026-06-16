export type FeeConfig = {
  percent: number;
  fixedCents: number;
  currency: string;
};

export type DonationBreakdown = {
  amountCents: number;
  chargeCents: number;
  estimatedFeeCents: number;
  netToCauseCents?: number;
};

export function dollarsToCents(dollars: string): number | null {
  const trimmed = dollars.trim();
  if (!trimmed) {
    return null;
  }

  const value = Number(trimmed);
  if (!Number.isFinite(value) || value <= 0) {
    return null;
  }

  return Math.round(value * 100);
}

export function formatUsd(cents: number): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(cents / 100);
}

export function estimateFeeCents(
  amountCents: number,
  percent: number,
  fixedCents: number,
): number {
  return Math.round((amountCents * percent) / 100) + fixedCents;
}

export function grossUpForFeeCents(
  intendedCents: number,
  percent: number,
  fixedCents: number,
): number {
  const denominator = 1 - percent / 100;
  const sum = intendedCents + fixedCents;

  return Math.ceil(sum / denominator);
}

export function calculateDonationBreakdown(
  amountCents: number,
  donorCoversFee: boolean,
  feeConfig: FeeConfig,
): DonationBreakdown {
  if (donorCoversFee) {
    const chargeCents = grossUpForFeeCents(
      amountCents,
      feeConfig.percent,
      feeConfig.fixedCents,
    );

    return {
      amountCents,
      chargeCents,
      estimatedFeeCents: chargeCents - amountCents,
    };
  }

  const estimatedFeeCents = estimateFeeCents(
    amountCents,
    feeConfig.percent,
    feeConfig.fixedCents,
  );

  return {
    amountCents,
    chargeCents: amountCents,
    estimatedFeeCents,
    netToCauseCents: amountCents - estimatedFeeCents,
  };
}
