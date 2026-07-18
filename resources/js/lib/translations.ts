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
  donationsPage: {
    eyebrow: string;
    pageTitle: string;
    pageIntro: string;
    breadcrumbHome: string;
    searchPlaceholder: string;
    searchButton: string;
    clearSearch: string;
    allCategories: string;
    noResults: string;
    noResultsSearch: string;
    resetFilters: string;
    categories: string;
    campaignsCount: string;
    campaignsCountOne: string;
    donateNow: string;
    goalReached: string;
  };
  donatePage: {
    eyebrow: string;
    generalTitle: string;
    continueToPayment: string;
    preparingCheckout: string;
    donateAmount: string;
    processing: string;
    backToGift: string;
    chooseAmount: string;
    customAmount: string;
    customAmountPlaceholder: string;
    coverFeeLabel: string;
    breakdownGift: string;
    breakdownFee: string;
    breakdownTotal: string;
    breakdownFeeNote: string;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    country: string;
    countryPlaceholder: string;
    anonymousLabel: string;
    message: string;
    messagePlaceholder: string;
    campaignUnavailable: string;
    goalReachedNotice: string;
    generalDonateCta: string;
    minAmountError: string;
    paymentError: string;
    confirmTitle: string;
    confirmBody: string;
    orPayWithCard: string;
    frequencyLabel: string;
    frequencyOneTime: string;
    frequencyWeekly: string;
    frequencyMonthly: string;
    frequencyQuarterly: string;
    frequencyYearly: string;
    perMonthSuffix: string;
    allocationTarget: string;
    allocationAmount: string;
    generalFundOption: string;
    addCampaignAllocation: string;
    removeAllocation: string;
    totalPerCycle: string;
    allocationAmountError: string;
    loadingCampaigns: string;
  };
  thankYouPage: {
    title: string;
    confirming: string;
    confirmingBody: string;
    successTitle: string;
    successGeneral: string;
    successCampaign: string;
    receiptNote: string;
    recurringNote: string;
    manageSubscription: string;
    failedTitle: string;
    failedBody: string;
    retry: string;
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
    donationsPage: {
      eyebrow: "Give Hope",
      pageTitle: "Donations",
      pageIntro:
        "Choose a campaign and make a gift that directly supports families across Egypt.",
      breadcrumbHome: "Home",
      searchPlaceholder: "Search campaigns…",
      searchButton: "Search",
      clearSearch: "Clear search",
      allCategories: "All",
      noResults: "No campaigns in this category.",
      noResultsSearch: "No results for your search.",
      resetFilters: "Clear filters",
      categories: "Categories",
      campaignsCount: "campaigns",
      campaignsCountOne: "campaign",
      donateNow: "Donate Now",
      goalReached: "Goal reached",
    },
    donatePage: {
      eyebrow: "Donate",
      generalTitle: "General Donation",
      continueToPayment: "Continue to payment",
      preparingCheckout: "Preparing checkout…",
      donateAmount: "Donate",
      processing: "Processing…",
      backToGift: "Edit gift details",
      chooseAmount: "Choose an amount",
      customAmount: "Custom amount (USD)",
      customAmountPlaceholder: "e.g. 75.00",
      coverFeeLabel:
        "Cover the processing fee so 100% of my gift reaches the cause.",
      breakdownGift: "Your gift",
      breakdownFee: "Processing fee",
      breakdownTotal: "Total charged",
      breakdownFeeNote: "in fees will be deducted so the cause receives about",
      firstName: "First name",
      lastName: "Last name",
      email: "Email",
      phone: "Phone (optional)",
      country: "Country (optional)",
      countryPlaceholder: "Select a country",
      anonymousLabel: "Don't show my name publicly",
      message: "Message (optional)",
      messagePlaceholder: "Add a note with your gift",
      campaignUnavailable: "This campaign is not accepting donations right now.",
      goalReachedNotice: "This campaign has reached its goal. Thank you for your support!",
      generalDonateCta: "Make a general donation",
      minAmountError: "Minimum donation is",
      paymentError: "Could not start checkout.",
      confirmTitle: "Confirming your donation…",
      confirmBody: "Please wait while we verify your payment.",
      orPayWithCard: "Or pay with card",
      frequencyLabel: "How often would you like to give?",
      frequencyOneTime: "One-time",
      frequencyWeekly: "Weekly",
      frequencyMonthly: "Monthly",
      frequencyQuarterly: "Quarterly",
      frequencyYearly: "Yearly",
      perMonthSuffix: "/cycle",
      allocationTarget: "Campaign or fund",
      allocationAmount: "Amount",
      generalFundOption: "General Fund",
      addCampaignAllocation: "Add another campaign",
      removeAllocation: "Remove",
      totalPerCycle: "Total per cycle:",
      allocationAmountError: "Amount is below the minimum donation.",
      loadingCampaigns: "Loading campaigns…",
    },
    thankYouPage: {
      title: "Thank you",
      confirming: "Confirming your donation…",
      confirmingBody:
        "Please wait while we verify your payment. This usually takes a few seconds.",
      successTitle: "Thank you!",
      successGeneral: "Your gift will support our general programs.",
      successCampaign: "It is designated for",
      receiptNote: "A receipt is on its way to",
      recurringNote: "This is a monthly recurring donation. You will be charged automatically each month.",
      manageSubscription: "Manage your monthly donation",
      failedTitle: "Payment could not be completed",
      failedBody: "Your card was not charged. You can try again from the donate page.",
      retry: "Try again",
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
    donationsPage: {
      eyebrow: "امنح الأمل",
      pageTitle: "التبرعات",
      pageIntro: "اختر حملة وقدّم تبرعًا يدعم العائلات في أنحاء مصر.",
      breadcrumbHome: "الرئيسية",
      searchPlaceholder: "ابحث في الحملات…",
      searchButton: "بحث",
      clearSearch: "مسح البحث",
      allCategories: "الكل",
      noResults: "لا توجد حملات في هذه الفئة.",
      noResultsSearch: "لا توجد نتائج لبحثك.",
      resetFilters: "مسح الفلاتر",
      categories: "الفئات",
      campaignsCount: "حملات",
      campaignsCountOne: "حملة",
      donateNow: "تبرّع الآن",
      goalReached: "تم بلوغ الهدف",
    },
    donatePage: {
      eyebrow: "تبرّع",
      generalTitle: "تبرع عام",
      continueToPayment: "متابعة إلى الدفع",
      preparingCheckout: "جاري تجهيز الدفع…",
      donateAmount: "تبرّع",
      processing: "جاري المعالجة…",
      backToGift: "تعديل تفاصيل التبرع",
      chooseAmount: "اختر المبلغ",
      customAmount: "مبلغ مخصص (دولار)",
      customAmountPlaceholder: "مثال: 75.00",
      coverFeeLabel: "تغطية رسوم المعالجة حتى يصل 100% من تبرعي للمستفيدين.",
      breakdownGift: "تبرعك",
      breakdownFee: "رسوم المعالجة",
      breakdownTotal: "إجمالي الخصم",
      breakdownFeeNote: "ستُخصم رسوم بحوالي",
      firstName: "الاسم الأول",
      lastName: "اسم العائلة",
      email: "البريد الإلكتروني",
      phone: "الهاتف (اختياري)",
      country: "الدولة (اختياري)",
      countryPlaceholder: "اختر الدولة",
      anonymousLabel: "لا تعرض اسمي علنًا",
      message: "رسالة (اختياري)",
      messagePlaceholder: "أضف ملاحظة مع تبرعك",
      campaignUnavailable: "هذه الحملة لا تقبل التبرعات حالياً.",
      goalReachedNotice: "بلغت هذه الحملة هدفها. شكرًا لدعمكم!",
      generalDonateCta: "تبرع عام",
      minAmountError: "الحد الأدنى للتبرع هو",
      paymentError: "تعذّر بدء عملية الدفع.",
      confirmTitle: "جاري تأكيد تبرعك…",
      confirmBody: "يرجى الانتظار أثناء التحقق من الدفع.",
      orPayWithCard: "أو ادفع بالبطاقة",
      frequencyLabel: "كم مرة تريد التبرع؟",
      frequencyOneTime: "مرة واحدة",
      frequencyWeekly: "أسبوعيًا",
      frequencyMonthly: "شهريًا",
      frequencyQuarterly: "ربع سنوي",
      frequencyYearly: "سنويًا",
      perMonthSuffix: "/دورة",
      allocationTarget: "الحملة أو الصندوق",
      allocationAmount: "المبلغ",
      generalFundOption: "الصندوق العام",
      addCampaignAllocation: "أضف حملة أخرى",
      removeAllocation: "إزالة",
      totalPerCycle: "المجموع لكل دورة:",
      allocationAmountError: "المبلغ أقل من الحد الأدنى للتبرع.",
      loadingCampaigns: "جارٍ تحميل الحملات…",
    },
    thankYouPage: {
      title: "شكرًا لك",
      confirming: "جاري تأكيد تبرعك…",
      confirmingBody: "يرجى الانتظار أثناء التحقق من الدفع. يستغرق ذلك عادة بضع ثوانٍ.",
      successTitle: "شكرًا لك!",
      successGeneral: "سيُوجَّه تبرعك لدعم برامجنا العامة.",
      successCampaign: "مُخصَّص لـ",
      receiptNote: "سيتم إرسال إيصال إلى",
      recurringNote: "هذا تبرع شهري متكرر. سيتم خصم المبلغ تلقائيًا كل شهر.",
      manageSubscription: "إدارة تبرعك الشهري",
      failedTitle: "تعذّر إتمام الدفع",
      failedBody: "لم يتم خصم المبلغ من بطاقتك. يمكنك المحاولة مرة أخرى من صفحة التبرع.",
      retry: "حاول مرة أخرى",
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