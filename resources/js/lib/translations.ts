// Shared translations dictionary. In production, inject via Inertia shared
// props (e.g. `usePage().props.translations`). The shape below is what the
// Site components consume.

export type Locale = "en" | "ar";

export interface NavItem {
  label: string;
  href: string;
}

export interface SiteTranslations {
  nav: {
    home: string;
    news: string;
    activities: string;
    donations: string;
    about: string;
    contact: string;
    donateCta: string;
    menu: string;
  };
  hero: {
    eyebrow: string;
    title: string;
    subtitle: string;
    donateCta: string;
    learnMore: string;
  };
  mission: {
    eyebrow: string;
    title: string;
    body: string;
    readMore: string;
    statNumber: string;
    statCaption: string;
  };
  message: {
    eyebrow: string;
    title: string;
    body: string;
    readMore: string;
  };
  donationCallout: {
    eyebrow: string;
    title: string;
    subtitle: string;
    cta: string;
  };
  news: {
    eyebrow: string;
    title: string;
    intro: string;
    seeMore: string;
    readArticle: string;
  };
  activities: {
    eyebrow: string;
    title: string;
    intro: string;
    seeMore: string;
  };
  volunteers: {
    eyebrow: string;
    title: string;
    intro: string;
    seeMore: string;
  };
  footer: {
    blurb: string;
    pagesTitle: string;
    pages: NavItem[];
    newsletterTitle: string;
    newsletterSubtitle: string;
    emailPlaceholder: string;
    subscribe: string;
    copyright: string;
    bottomLinks: NavItem[];
  };
  langSwitch: {
    en: string;
    ar: string;
    label: string;
  };
}

export const translations: Record<Locale, SiteTranslations> = {
  en: {
    nav: {
      home: "Home",
      news: "News",
      activities: "Activities",
      donations: "Donations",
      about: "About",
      contact: "Contact",
      donateCta: "Make a Donation",
      menu: "Open menu",
    },
    hero: {
      eyebrow: "New Egypt Group",
      title: "Give a helping hand for children in need",
      subtitle:
        "We support communities across Egypt through education, healthcare, food distribution, and lasting community development.",
      donateCta: "Donate Now",
      learnMore: "Learn More",
    },
    mission: {
      eyebrow: "Who We Are",
      title: "Our mission is to build a stronger Egypt, one community at a time",
      body: "From classrooms in Upper Egypt to clinics in rural villages, our teams partner with local leaders to deliver real, measurable change — every donation funds programs that lift entire families out of hardship.",
      readMore: "Read More",
      statNumber: "673M",
      statCaption: "people worldwide face hunger today — UN",
    },
    message: {
      eyebrow: "Our Message",
      title: "Compassion in action — every single day",
      body: "We believe dignity is a right, not a privilege. Through transparent giving, on-the-ground volunteers, and long-term programs, we turn generosity into lasting impact for the children, families, and elders who need it most.",
      readMore: "Read More",
    },
    donationCallout: {
      eyebrow: "Take Action",
      title: "Make a Donation",
      subtitle:
        "Your contribution creates lasting positive change for families across Egypt.",
      cta: "Donate Now",
    },
    news: {
      eyebrow: "News & Stories",
      title: "Updates from the field",
      intro:
        "Read the latest from our programs, partner communities, and the teams making it all happen.",
      seeMore: "See More",
      readArticle: "Read article",
    },
    activities: {
      eyebrow: "What We Do",
      title: "Our Activities",
      intro:
        "From food drives to scholarship programs, our work spans the issues that matter most to Egyptian families.",
      seeMore: "See More",
    },
    volunteers: {
      eyebrow: "The People",
      title: "Our Volunteers",
      intro:
        "Meet a few of the hundreds of volunteers giving their time, skills, and heart to our mission.",
      seeMore: "See More",
    },
    footer: {
      blurb:
        "New Egypt Group is a non-profit organization dedicated to lifting communities across Egypt through education, healthcare, and food security.",
      pagesTitle: "Pages",
      pages: [
        { label: "About us", href: "#about" },
        { label: "Services", href: "#services" },
        { label: "Team", href: "#team" },
        { label: "Bylaws", href: "#bylaws" },
        { label: "Join Us", href: "#join" },
      ],
      newsletterTitle: "Stay in the loop",
      newsletterSubtitle: "Get monthly stories and updates from our programs.",
      emailPlaceholder: "Your email address",
      subscribe: "Subscribe",
      copyright: "© 2026 New Egypt Group. All rights reserved.",
      bottomLinks: [
        { label: "Privacy", href: "#privacy" },
        { label: "Terms", href: "#terms" },
        { label: "FAQs", href: "#faqs" },
        { label: "Contact", href: "#contact" },
      ],
    },
    langSwitch: { en: "EN", ar: "AR", label: "Language" },
  },
  ar: {
    nav: {
      home: "الرئيسية",
      news: "الأخبار",
      activities: "الأنشطة",
      donations: "التبرعات",
      about: "من نحن",
      contact: "تواصل معنا",
      donateCta: "تبرّع الآن",
      menu: "افتح القائمة",
    },
    hero: {
      eyebrow: "مجموعة مصر الجديدة",
      title: "مدّ يد العون للأطفال",
      subtitle:
        "ندعم المجتمعات في جميع أنحاء مصر من خلال التعليم والرعاية الصحية وتوزيع الغذاء والتنمية المجتمعية المستدامة.",
      donateCta: "تبرّع الآن",
      learnMore: "اعرف المزيد",
    },
    mission: {
      eyebrow: "من نحن",
      title: "رسالتنا أن نبني مصر أقوى، مجتمعًا تلو الآخر",
      body: "من فصول الصعيد إلى عيادات القرى، تعمل فرقنا جنبًا إلى جنب مع القيادات المحلية لإحداث تغيير ملموس — كل تبرع يموّل برامج تُخرج عائلات بأكملها من قلب المعاناة.",
      readMore: "اقرأ المزيد",
      statNumber: "٦٧٣ مليون",
      statCaption: "شخص حول العالم يعاني الجوع اليوم — الأمم المتحدة",
    },
    message: {
      eyebrow: "رسالتنا",
      title: "رحمة تتحوّل إلى فعل، كل يوم",
      body: "نؤمن بأن الكرامة حقٌ لا امتياز. من خلال شفافية العطاء، ومتطوعين على الأرض، وبرامج طويلة الأمد، نحوّل كرم المانحين إلى أثر دائم في حياة الأطفال والعائلات والمسنين.",
      readMore: "اقرأ المزيد",
    },
    donationCallout: {
      eyebrow: "ساهم الآن",
      title: "قدّم تبرّعك",
      subtitle: "مساهمتك تصنع تغييرًا إيجابيًا دائمًا للعائلات في مصر.",
      cta: "تبرّع الآن",
    },
    news: {
      eyebrow: "أخبار وقصص",
      title: "أحدث المستجدات من الميدان",
      intro:
        "اطّلع على آخر أخبار برامجنا ومجتمعاتنا الشريكة والفِرق التي تُحقّق الأثر.",
      seeMore: "عرض المزيد",
      readArticle: "اقرأ المقال",
    },
    activities: {
      eyebrow: "ماذا نفعل",
      title: "أنشطتنا",
      intro:
        "من قوافل الإغاثة إلى المنح الدراسية، يمتدّ عملنا ليطال أهم القضايا للأسرة المصرية.",
      seeMore: "عرض المزيد",
    },
    volunteers: {
      eyebrow: "الفريق",
      title: "متطوعونا",
      intro:
        "تعرّف على بعض من مئات المتطوعين الذين يهبون وقتهم ومهاراتهم وقلوبهم لرسالتنا.",
      seeMore: "عرض المزيد",
    },
    footer: {
      blurb:
        "مجموعة مصر الجديدة منظمة غير ربحية تكرّس عملها لرفع المجتمعات في مصر عبر التعليم والصحة والأمن الغذائي.",
      pagesTitle: "الصفحات",
      pages: [
        { label: "من نحن", href: "#about" },
        { label: "الخدمات", href: "#services" },
        { label: "الفريق", href: "#team" },
        { label: "اللوائح", href: "#bylaws" },
        { label: "انضمّ إلينا", href: "#join" },
      ],
      newsletterTitle: "ابقَ على تواصل",
      newsletterSubtitle: "قصص وتحديثات شهرية من برامجنا.",
      emailPlaceholder: "بريدك الإلكتروني",
      subscribe: "اشترك",
      copyright: "© ٢٠٢٦ مجموعة مصر الجديدة. جميع الحقوق محفوظة.",
      bottomLinks: [
        { label: "الخصوصية", href: "#privacy" },
        { label: "الشروط", href: "#terms" },
        { label: "الأسئلة الشائعة", href: "#faqs" },
        { label: "تواصل", href: "#contact" },
      ],
    },
    langSwitch: { en: "EN", ar: "ع", label: "اللغة" },
  },
};

export const dirFor = (locale: Locale): "ltr" | "rtl" =>
  locale === "ar" ? "rtl" : "ltr";