import { formatUsd } from "@/lib/donation-fee";

const PRESET_AMOUNTS = [2500, 5000, 10000, 25000];

type AmountChipsProps = {
  amountCents: number;
  customAmount: string;
  minAmountCents: number;
  labels: {
    chooseAmount: string;
    customAmount: string;
    customAmountPlaceholder: string;
  };
  onPresetSelect: (cents: number) => void;
  onCustomAmountChange: (value: string) => void;
};

export function AmountChips({
  amountCents,
  customAmount,
  minAmountCents,
  labels,
  onPresetSelect,
  onCustomAmountChange,
}: AmountChipsProps) {
  return (
    <div>
      <p className="mb-3 text-sm font-medium text-ink">{labels.chooseAmount}</p>
      <div className="flex flex-wrap gap-2" role="group" aria-label={labels.chooseAmount}>
        {PRESET_AMOUNTS.map((preset) => {
          const selected = amountCents === preset && !customAmount;

          return (
            <button
              key={preset}
              type="button"
              aria-pressed={selected}
              onClick={() => onPresetSelect(preset)}
              className={`rounded-lg px-4 py-2.5 text-sm font-semibold ring-1 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-action-red/40 ${
                selected
                  ? "bg-action-red text-white ring-action-red"
                  : "bg-white text-ink ring-black/10 hover:ring-action-red/40"
              }`}
            >
              {formatUsd(preset)}
            </button>
          );
        })}
      </div>
      <label className="mt-4 block text-sm font-medium text-ink" htmlFor="custom-amount">
        {labels.customAmount}
      </label>
      <input
        id="custom-amount"
        type="number"
        min={minAmountCents / 100}
        step="0.01"
        value={customAmount}
        onChange={(event) => onCustomAmountChange(event.target.value)}
        placeholder={labels.customAmountPlaceholder}
        className="mt-1 w-full rounded-lg border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-action-red focus:ring-2 focus:ring-action-red/20"
      />
    </div>
  );
}

export { PRESET_AMOUNTS };
