// Shared translations dictionary. In production, inject via Inertia shared
// props (e.g. `usePage().props.translations`). The shape below is what the
// Site components consume.

export type Locale = "en" | "ar";

export interface NavItem {
  label: string;
  href: string;
}

export interface SiteTranslations {
  brandName: string;
  nav: {
    home: string;
    news: string;
    campaigns: string;
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
  newsPage: {
    pageTitle: string;
    pageIntro: string;
    searchPlaceholder: string;
    allCategories: string;
    noResults: string;
    categories: string;
    backToNews: string;
    publishedOn: string;
  };
  campaigns: {
    eyebrow: string;
    title: string;
    intro: string;
    seeMore: string;
    readCampaign: string;
  };
  campaignsPage: {
    pageTitle: string;
    pageIntro: string;
    searchPlaceholder: string;
    allCategories: string;
    noResults: string;
    categories: string;
    backToCampaigns: string;
    startsOn: string;
    endsOn: string;
  };
  faqsPage: {
    eyebrow: string;
    pageTitle: string;
    pageIntro: string;
    noResults: string;
  };
  contactPage: {
    eyebrow: string;
    pageTitle: string;
    pageIntro: string;
    fullname: string;
    email: string;
    phone: string;
    subject: string;
    message: string;
    submit: string;
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
    brandName: "New Egypt Group",
    nav: {
      home: "Home",
      news: "News",
      campaigns: "Campaigns",
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
      body: "At New Egypt Group Inc, our mission is to fight poverty and digital illiteracy worldwide by promoting digital literacy as a constitutional duty. We aim to make a significant impact on climate change and biodiversity by generating innovative ideas, empowering local leaders, and influencing policies. Through media production and community connections, we raise awareness and facilitate collaboration between the USA and Egypt. Our mission extends globally as we strive to create a positive difference.",
      readMore: "Read More",
      statNumber: "673M",
      statCaption: "people worldwide face hunger today — UN",
    },
    message: {
      eyebrow: "Our Message",
      title: "Compassion in action — every single day",
      body: "New Egypt Group is a non-profit organization that builds bridges between the Egyptian community in America and their beloved country, Egypt. It carries out many activities including education initiatives for children as well empowerment of women so they can build futures generations upon generation sweepingly exploited within this beautiful land! The group is an active, supporting some villages in Upper Egypt and the Delta region. A visit to a village by Al-Kisa Bank led them start their series of support for villages unable to face life’s difficulties during 2014; starting with Qena Governorate was where it all started!",
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
    newsPage: {
      pageTitle: "News & Stories",
      pageIntro: "Explore all our latest updates, stories, and articles from the field.",
      searchPlaceholder: "Search news…",
      allCategories: "All Categories",
      noResults: "No articles found. Try a different search or category.",
      categories: "Categories",
      backToNews: "Back to News",
      publishedOn: "Published on",
    },
    campaigns: {
      eyebrow: "What We Do",
      title: "Our Campaigns",
      intro:
        "From food drives to scholarship programs, our campaigns address the issues that matter most to Egyptian families.",
      seeMore: "See More",
      readCampaign: "View campaign",
    },
    campaignsPage: {
      pageTitle: "Our Campaigns",
      pageIntro: "Explore all our active and completed campaigns making a difference across Egypt.",
      searchPlaceholder: "Search campaigns…",
      allCategories: "All Categories",
      noResults: "No campaigns found. Try a different search or category.",
      categories: "Categories",
      backToCampaigns: "Back to Campaigns",
      startsOn: "Starts on",
      endsOn: "Ends on",
    },
    faqsPage: {
      eyebrow: "Help Center",
      pageTitle: "Frequently Asked Questions",
      pageIntro: "Find answers to common questions about our programs, donations, and how to get involved.",
      noResults: "No FAQs are available at the moment.",
    },
    contactPage: {
      eyebrow: "Get in Touch",
      pageTitle: "Contact Us",
      pageIntro: "Send us a message and our team will get back to you as soon as possible.",
      fullname: "Full name",
      email: "Email address",
      phone: "Phone (optional)",
      subject: "Subject",
      message: "Message",
      submit: "Send Message",
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
        { label: "News", href: "/news" },
        { label: "Campaigns", href: "/campaigns" },
        { label: "Terms & Conditions", href: "/terms" },
        { label: "Privacy Policy", href: "/privacy" },
        { label: "FAQs", href: "/faqs" },
        { label: "Contact Us", href: "/contact" },
      ],
      newsletterTitle: "Stay in the loop",
      newsletterSubtitle: "Get monthly stories and updates from our programs.",
      emailPlaceholder: "Your email address",
      subscribe: "Subscribe",
      copyright: "© 2026 New Egypt Group. All rights reserved.",
      bottomLinks: [
        { label: "Privacy", href: "/privacy" },
        { label: "Terms", href: "/terms" },
        { label: "FAQs", href: "/faqs" },
        { label: "Contact", href: "/contact" },
      ],
    },
    langSwitch: { en: "EN", ar: "AR", label: "Language" },
  },
  ar: {
    brandName: "مجموعة نيوإيجيبت",
    nav: {
      home: "الرئيسية",
      news: "الأخبار",
      campaigns: "الأنشطة",
      donations: "التبرعات",
      about: "من نحن",
      contact: "تواصل معنا",
      donateCta: "تبرّع الآن",
      menu: "افتح القائمة",
    },
    hero: {
      eyebrow: "مجموعة نيوإيجيبت",
      title: "مدّ يد العون للأطفال",
      subtitle:
        "ندعم المجتمعات في جميع أنحاء مصر من خلال التعليم والرعاية الصحية وتوزيع الغذاء والتنمية المجتمعية المستدامة.",
      donateCta: "تبرّع الآن",
      learnMore: "اعرف المزيد",
    },
    mission: {
      eyebrow: "من نحن",
      title: "رسالتنا أن نبني مصر أقوى، مجتمعًا تلو الآخر",
      body: "تتمثل مهمتنا في مجموعة نيوإيجيبت في مكافحة الفقر والأمية الرقمية في جميع أنحاء العالم من خلال تعزيز محو الأمية الرقمية كواجب دستوري. نهدف إلى إحداث تأثير كبير على تغير المناخ والتنوع البيولوجي من خلال توليد أفكار مبتكرة وتمكين القادة المحليين والتأثير على السياسات. من خلال الإنتاج الإعلامي والتواصل المجتمعي ، نقوم برفع مستوى الوعي وتسهيل التعاون بين الولايات المتحدة الأمريكية ومصر. مهمتنا تمتد على الصعيد العالمي ونحن نسعى جاهدين لخلق فرق إيجابي.",
      readMore: "اقرأ المزيد",
      statNumber: "٦٧٣ مليون",
      statCaption: "شخص حول العالم يعاني الجوع اليوم — الأمم المتحدة",
    },
    message: {
      eyebrow: "رسالتنا",
      title: "رحمة تتحوّل إلى فعل، كل يوم",
      body: "مجموعة نيوإيجيبت هي منظمة غيرهادفة للربح تعمل على بناء الجسور بين الجالية المصرية في الولايات المتحده  ودولتهم الحبيبة مصر. وتقوم بالعديد من الأنشطة بما في ذلك المبادرات التعليمية للأطفال وكذلك تمكين المرأة حتى تتمكن من بناء أجيال مستقبلية قادرة على بناء مستقبل مشرق . المجموعة نشطة ، وتدعم بعض القرى في صعيد مصر ومنطقة الدلتا. و قد أدت زيارة قام بها بنك الكساء إلى إحدى القرى إلى بدء سلسلة دعمهم للقرى غير القادرة على مواجهة صعوبات الحياة خلال عام 2014 ؛ بدءا من محافظة قنا حيث كانت البدايه!",
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
    newsPage: {
      pageTitle: "أخبار وقصص",
      pageIntro: "تصفّح جميع أحدث أخبارنا وقصصنا ومقالاتنا من الميدان.",
      searchPlaceholder: "ابحث في الأخبار…",
      allCategories: "كل الفئات",
      noResults: "لا توجد مقالات. جرّب بحثًا مختلفًا أو فئة أخرى.",
      categories: "الفئات",
      backToNews: "العودة إلى الأخبار",
      publishedOn: "نُشر في",
    },
    campaigns: {
      eyebrow: "ماذا نفعل",
      title: "حملاتنا",
      intro:
        "من قوافل الإغاثة إلى المنح الدراسية، تمتدّ حملاتنا لمعالجة أهم القضايا للأسرة المصرية.",
      seeMore: "عرض المزيد",
      readCampaign: "عرض الحملة",
    },
    campaignsPage: {
      pageTitle: "حملاتنا",
      pageIntro: "استكشف جميع حملاتنا النشطة والمكتملة التي تصنع فرقًا في أنحاء مصر.",
      searchPlaceholder: "ابحث في الأنشطة…",
      allCategories: "كل الفئات",
      noResults: "لا توجد حملات. جرّب بحثًا مختلفًا أو فئة أخرى.",
      categories: "الفئات",
      backToCampaigns: "العودة إلى الأنشطة",
      startsOn: "تبدأ في",
      endsOn: "تنتهي في",
    },
    faqsPage: {
      eyebrow: "مركز المساعدة",
      pageTitle: "الأسئلة الشائعة",
      pageIntro: "اعثر على إجابات للأسئلة الشائعة حول برامجنا والتبرعات وكيفية المشاركة.",
      noResults: "لا توجد أسئلة شائعة متاحة حاليًا.",
    },
    contactPage: {
      eyebrow: "تواصل معنا",
      pageTitle: "اتصل بنا",
      pageIntro: "أرسل لنا رسالة وسيتواصل معك فريقنا في أقرب وقت ممكن.",
      fullname: "الاسم الكامل",
      email: "البريد الإلكتروني",
      phone: "الهاتف (اختياري)",
      subject: "الموضوع",
      message: "الرسالة",
      submit: "إرسال الرسالة",
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
        "مجموعة نيوإيجيبت منظمة غير ربحية تكرّس عملها لرفع المجتمعات في مصر عبر التعليم والصحة والأمن الغذائي.",
      pagesTitle: "الصفحات",
      pages: [
        { label: "الأخبار", href: "/news" },
        { label: "الأنشطة", href: "/campaigns" },
        { label: "الشروط والأحكام", href: "/terms" },
        { label: "سياسة الخصوصية", href: "/privacy" },
        { label: "الأسئلة الشائعة", href: "/faqs" },
        { label: "تواصل معنا", href: "/contact" },
      ],
      newsletterTitle: "ابقَ على تواصل",
      newsletterSubtitle: "قصص وتحديثات شهرية من برامجنا.",
      emailPlaceholder: "بريدك الإلكتروني",
      subscribe: "اشترك",
      copyright: "© ٢٠٢٦ مجموعة نيوإيجيبت. جميع الحقوق محفوظة.",
      bottomLinks: [
        { label: "الخصوصية", href: "/privacy" },
        { label: "الشروط", href: "/terms" },
        { label: "الأسئلة الشائعة", href: "/faqs" },
        { label: "تواصل", href: "/contact" },
      ],
    },
    langSwitch: { en: "EN", ar: "ع", label: "اللغة" },
  },
};

export const dirFor = (locale: Locale): "ltr" | "rtl" =>
  locale === "ar" ? "rtl" : "ltr";