import { formatUsd } from "@/lib/donation-fee";

export type CampaignOption = {
  id: number;
  title: string;
};

export type AllocationRow = {
  id: string;
  target: "general" | number;
  amount: string;
};

type AllocationEditorProps = {
  rows: AllocationRow[];
  campaigns: CampaignOption[];
  campaignsLoading: boolean;
  minAmountCents: number;
  labels: {
    allocationTarget: string;
    allocationAmount: string;
    generalFundOption: string;
    addCampaignAllocation: string;
    removeAllocation: string;
    totalPerCycle: string;
    allocationAmountError: string;
    loadingCampaigns: string;
  };
  onRowsChange: (rows: AllocationRow[]) => void;
};

function createRowId(): string {
  return `allocation-${Math.random().toString(36).slice(2)}-${Date.now()}`;
}

export function createAllocationRow(target: AllocationRow["target"], amount: string): AllocationRow {
  return { id: createRowId(), target, amount };
}

export function AllocationEditor({
  rows,
  campaigns,
  campaignsLoading,
  minAmountCents,
  labels,
  onRowsChange,
}: AllocationEditorProps) {
  const totalCents = rows.reduce((sum, row) => {
    const value = Math.round((Number.parseFloat(row.amount) || 0) * 100);
    return sum + (Number.isFinite(value) ? value : 0);
  }, 0);

  const usedCampaignIds = new Set(
    rows.map((row) => row.target).filter((target): target is number => target !== "general"),
  );
  const hasGeneralRow = rows.some((row) => row.target === "general");
  const availableCampaigns = campaigns.filter((campaign) => !usedCampaignIds.has(campaign.id));
  const canAddRow = rows.length < 10 && (availableCampaigns.length > 0 || !hasGeneralRow);

  const updateRow = (id: string, patch: Partial<AllocationRow>) => {
    onRowsChange(rows.map((row) => (row.id === id ? { ...row, ...patch } : row)));
  };

  const removeRow = (id: string) => {
    onRowsChange(rows.filter((row) => row.id !== id));
  };

  const addRow = () => {
    const nextTarget: AllocationRow["target"] = !hasGeneralRow
      ? "general"
      : availableCampaigns[0]?.id ?? "general";

    onRowsChange([...rows, createAllocationRow(nextTarget, "")]);
  };

  return (
    <div className="space-y-3">
      {rows.map((row) => {
        const amountCents = Math.round((Number.parseFloat(row.amount) || 0) * 100);
        const amountInvalid = row.amount.trim().length > 0 && amountCents < minAmountCents;
        const targetIsUnknownCampaign =
          row.target !== "general" && !campaigns.some((campaign) => campaign.id === row.target);

        return (
          <div
            key={row.id}
            className="flex flex-col gap-2 rounded-lg bg-surface-soft p-3 ring-1 ring-black/5 sm:flex-row sm:items-start sm:gap-3"
          >
            <div className="flex-1">
              <label className="sr-only" htmlFor={`allocation-target-${row.id}`}>
                {labels.allocationTarget}
              </label>
              <select
                id={`allocation-target-${row.id}`}
                value={row.target === "general" ? "general" : String(row.target)}
                onChange={(event) =>
                  updateRow(row.id, {
                    target: event.target.value === "general" ? "general" : Number(event.target.value),
                  })
                }
                className="w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-action-red focus:ring-2 focus:ring-action-red/20"
              >
                <option value="general" disabled={hasGeneralRow && row.target !== "general"}>
                  {labels.generalFundOption}
                </option>
                {targetIsUnknownCampaign ? (
                  <option value={row.target}>{labels.loadingCampaigns}</option>
                ) : null}
                {campaigns.map((campaign) => (
                  <option
                    key={campaign.id}
                    value={campaign.id}
                    disabled={usedCampaignIds.has(campaign.id) && campaign.id !== row.target}
                  >
                    {campaign.title}
                  </option>
                ))}
              </select>
            </div>
            <div className="sm:w-40">
              <label className="sr-only" htmlFor={`allocation-amount-${row.id}`}>
                {labels.allocationAmount}
              </label>
              <input
                id={`allocation-amount-${row.id}`}
                type="number"
                min={minAmountCents / 100}
                step="0.01"
                value={row.amount}
                onChange={(event) => updateRow(row.id, { amount: event.target.value })}
                placeholder={formatUsd(minAmountCents)}
                className={`w-full rounded-lg border bg-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-action-red/20 ${
                  amountInvalid ? "border-action-red" : "border-black/10 focus:border-action-red"
                }`}
              />
              {amountInvalid ? (
                <p className="mt-1 text-xs text-action-red">{labels.allocationAmountError}</p>
              ) : null}
            </div>
            {rows.length > 1 ? (
              <button
                type="button"
                onClick={() => removeRow(row.id)}
                aria-label={labels.removeAllocation}
                className="self-start rounded-lg px-2 py-2.5 text-sm font-medium text-body-text hover:text-action-red sm:self-center"
              >
                &times;
              </button>
            ) : null}
          </div>
        );
      })}

      {canAddRow ? (
        <button
          type="button"
          onClick={addRow}
          disabled={campaignsLoading}
          className="text-sm font-medium text-action-red hover:underline disabled:opacity-50"
        >
          + {labels.addCampaignAllocation}
        </button>
      ) : null}

      <p className="text-sm font-semibold text-ink">
        {labels.totalPerCycle} {formatUsd(totalCents)}
      </p>
    </div>
  );
}
