import { describe, expect, it } from "vitest";
import {
  calculateDonationBreakdown,
  dollarsToCents,
  estimateFeeCents,
  grossUpForFeeCents,
} from "@/lib/donation-fee";

describe("donation-fee", () => {
  const feeConfig = { percent: 2.9, fixedCents: 30, currency: "USD" };

  it("converts dollars to cents", () => {
    expect(dollarsToCents("100")).toBe(10000);
    expect(dollarsToCents("25.50")).toBe(2550);
    expect(dollarsToCents("")).toBeNull();
  });

  it("grosses up fee for standard amount", () => {
    expect(grossUpForFeeCents(10000, 2.9, 30)).toBe(10330);
  });

  it("estimates fee when donor does not cover", () => {
    expect(estimateFeeCents(10000, 2.9, 30)).toBe(320);
  });

  it("calculates covered breakdown", () => {
    const breakdown = calculateDonationBreakdown(10000, true, feeConfig);

    expect(breakdown.amountCents).toBe(10000);
    expect(breakdown.chargeCents).toBe(10330);
    expect(breakdown.estimatedFeeCents).toBe(330);
  });

  it("calculates declined breakdown", () => {
    const breakdown = calculateDonationBreakdown(10000, false, feeConfig);

    expect(breakdown.amountCents).toBe(10000);
    expect(breakdown.chargeCents).toBe(10000);
    expect(breakdown.estimatedFeeCents).toBe(320);
    expect(breakdown.netToCauseCents).toBe(9680);
  });
});
