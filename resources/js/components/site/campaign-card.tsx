import { Link } from "@inertiajs/react";
import { ChevronsRight } from "lucide-react";
import { route } from "ziggy-js";

export type CampaignCardItem = {
  id: number;
  slug: string;
  title: string;
  thumbnail: string;
  goal_amount_cents: number | null;
  collected_amount_cents: number;
  open_donation_form?: boolean;
  status?: string;
};

type CampaignCardProps = {
  campaign: CampaignCardItem;
  donateLabel: string;
  goalReachedLabel: string;
};

export function CampaignCard({
  campaign,
  donateLabel,
  goalReachedLabel,
}: CampaignCardProps) {
  const hasGoal =
    campaign.goal_amount_cents !== null && campaign.goal_amount_cents > 0;
  const progress = hasGoal
    ? Math.min(
        100,
        Math.round(
          (campaign.collected_amount_cents / campaign.goal_amount_cents!) * 100,
        ),
      )
    : 0;
  const goalReached =
    hasGoal && campaign.collected_amount_cents >= campaign.goal_amount_cents!;

  return (
    <article
      className="flex flex-col overflow-hidden rounded-2xl bg-surface shadow-sm ring-1 ring-black/5"
    >
      <div className="aspect-[4/3] overflow-hidden bg-surface-soft">
        {campaign.thumbnail ? (
          <img
            src={campaign.thumbnail}
            alt={campaign.title}
            className="h-full w-full object-cover"
            loading="lazy"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center bg-surface-soft">
            <img
              src="/images/new-egypt-logo.png"
              alt=""
              className="h-16 w-16 object-contain opacity-40"
            />
          </div>
        )}
      </div>

      <div className="flex flex-1 flex-col gap-3 p-5 text-center">
        <h3 className="font-display text-lg font-bold leading-snug text-ink line-clamp-2">
          {campaign.title}
        </h3>

        {hasGoal ? (
          <div className="mx-auto w-full max-w-xs">
            <div className="h-1.5 overflow-hidden rounded-full bg-surface-soft">
              <div
                className="h-full rounded-full bg-action-red transition-all"
                style={{ width: `${progress}%` }}
              />
            </div>
            {goalReached ? (
              <p className="mt-2 text-xs font-medium text-gold">{goalReachedLabel}</p>
            ) : null}
          </div>
        ) : null}

        <div className="mt-auto flex justify-center pt-2">
          <Link
            href={route("campaigns.donate", campaign.slug)}
            className="inline-flex items-center gap-2 rounded-full bg-white pe-1 ps-1 py-1 text-sm font-semibold text-action-red shadow-md ring-1 ring-black/10 transition hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-action-red/40"
          >
            <span className="inline-flex size-8 items-center justify-center rounded-full bg-gold text-white">
              <ChevronsRight className="size-4 rtl:-scale-x-100" aria-hidden="true" />
            </span>
            <span className="pe-4">{donateLabel}</span>
          </Link>
        </div>
      </div>
    </article>
  );
}

export default CampaignCard;
