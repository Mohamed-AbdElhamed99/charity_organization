import type { SiteTranslations } from "../../lib/translations";
import { SectionHeading } from "./section-heading";
import { SiteButton } from "./site-button";
import { NewsCard, type NewsItem } from "./news-card";

export interface NewsSectionProps {
  t: SiteTranslations;
  news?: NewsItem[];
}

const DEFAULT_NEWS: NewsItem[] = [
  {
    id: 1,
    title: "5,000 meals delivered to families in Aswan this Ramadan",
    excerpt:
      "Our Ramadan food drive reached 1,200 households across three governorates thanks to local partners and volunteers.",
    image:
      "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80",
    href: "#news-1",
    category: "Food security",
  },
  {
    id: 2,
    title: "New scholarship program launches for 200 students",
    excerpt:
      "Working with public schools in Minya, we're funding full-year scholarships for top-performing students from low-income homes.",
    image:
      "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80",
    href: "#news-2",
    category: "Education",
  },
  {
    id: 3,
    title: "Mobile clinic visits 12 villages across Beni Suef",
    excerpt:
      "Our mobile health team provided free check-ups, vaccinations, and prescriptions to over 800 villagers in two weeks.",
    image:
      "https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&q=80",
    href: "#news-3",
    category: "Healthcare",
  },
  {
    id: 4,
    title: "Volunteers rebuild community center in Sohag",
    excerpt:
      "Over 80 volunteers spent the weekend renovating a shared community space that serves more than 600 local families.",
    image:
      "https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=800&q=80",
    href: "#news-4",
    category: "Community",
  },
];

export function NewsSection({ t, news = DEFAULT_NEWS }: NewsSectionProps) {
  return (
    <section id="news" className="bg-surface-soft py-20 md:py-28">
      <div className="mx-auto max-w-[1200px] px-6">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading
            eyebrow={t.news.eyebrow}
            title={t.news.title}
            intro={t.news.intro}
          />
          <SiteButton href="#all-news" variant="ghost">
            {t.news.seeMore}
          </SiteButton>
        </div>
        <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {news.map((item) => (
            <NewsCard key={item.id} item={item} readLabel={t.news.readArticle} />
          ))}
        </div>
      </div>
    </section>
  );
}

export default NewsSection;