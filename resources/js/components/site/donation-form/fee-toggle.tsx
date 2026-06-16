import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import {
  calculateDonationBreakdown,
  formatUsd,
  type FeeConfig,
} from "@/lib/donation-fee";

type FeeToggleProps = {
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
  amountCents: number;
  feeConfig: FeeConfig;
  label: string;
  labels: {
    breakdownGift: string;
    breakdownFee: string;
    breakdownTotal: string;
    breakdownFeeNote: string;
  };
};

export function FeeToggle({
  checked,
  onCheckedChange,
  amountCents,
  feeConfig,
  label,
  labels,
}: FeeToggleProps) {
  const breakdown = calculateDonationBreakdown(amountCents, checked, feeConfig);

  return (
    <div className="space-y-4">
      <div className="flex items-start gap-3">
        <Switch
          id="cover-fee"
          checked={checked}
          onCheckedChange={onCheckedChange}
          aria-describedby="fee-breakdown"
        />
        <Label htmlFor="cover-fee" className="text-sm leading-relaxed text-body-text">
          {label}
        </Label>
      </div>

      <div
        id="fee-breakdown"
        className="rounded-xl bg-surface-soft p-4 text-sm text-body-text ring-1 ring-black/5"
      >
        {checked ? (
          <p>
            {labels.breakdownGift} {formatUsd(breakdown.amountCents)} ·{" "}
            {labels.breakdownFee} {formatUsd(breakdown.estimatedFeeCents)} ·{" "}
            <span className="font-semibold text-ink">
              {labels.breakdownTotal} {formatUsd(breakdown.chargeCents)}
            </span>
          </p>
        ) : (
          <>
            <p>
              {labels.breakdownGift} {formatUsd(breakdown.amountCents)} ·{" "}
              <span className="font-semibold text-ink">
                {labels.breakdownTotal} {formatUsd(breakdown.chargeCents)}
              </span>
            </p>
            <p className="mt-2 text-xs text-body-text/70">
              About {formatUsd(breakdown.estimatedFeeCents)} {labels.breakdownFeeNote}{" "}
              {formatUsd(breakdown.netToCauseCents ?? 0)}.
            </p>
          </>
        )}
      </div>
    </div>
  );
}

export default FeeToggle;
