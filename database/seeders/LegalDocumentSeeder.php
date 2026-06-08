<?php

namespace Database\Seeders;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

/**
 * Seeds production-ready Terms & Conditions and Privacy Policy content
 * for New Egypt Group — an international non-profit operating in the US and Egypt.
 *
 * Idempotent: updateOrCreate keyed on document type.
 */
class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        LegalDocument::updateOrCreate(
            ['type' => LegalDocumentType::Terms->value],
            [
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms & Conditions',
                'body_ar' => $this->termsBodyAr(),
                'body_en' => $this->termsBodyEn(),
            ]
        );

        LegalDocument::updateOrCreate(
            ['type' => LegalDocumentType::Privacy->value],
            [
                'title_ar' => 'سياسة الخصوصية',
                'title_en' => 'Privacy Policy',
                'body_ar' => $this->privacyBodyAr(),
                'body_en' => $this->privacyBodyEn(),
            ]
        );

        $this->command->info('✅ Legal documents seeded (Terms & Privacy).');
    }

    private function termsBodyEn(): string
    {
        return <<<'HTML'
<h2>1. Agreement to Terms</h2>
<p>Welcome to New Egypt Group (“we,” “us,” or “our”). By accessing our website, making a donation, registering for an account, or using any of our services, you agree to be bound by these Terms &amp; Conditions. If you do not agree, please do not use our website or services.</p>
<p>New Egypt Group is a non-profit organization that supports communities in need across Egypt and engages donors and volunteers primarily in the United States and internationally. These Terms govern your use of our digital platforms and your relationship with us as a donor, volunteer, or visitor.</p>

<h2>2. Eligibility</h2>
<p>You must be at least 18 years old, or the age of majority in your jurisdiction, to make a donation or enter into a binding agreement with us. If you are donating on behalf of an organization, you represent that you have authority to bind that organization.</p>

<h2>3. Donations</h2>
<p>All donations made through our website or approved offline channels are voluntary gifts to support our charitable programs. Unless otherwise stated at the time of giving:</p>
<ul>
<li>Donations are generally non-refundable. If you believe a donation was made in error, contact us within 14 days at the email address on our Contact page.</li>
<li>Designated gifts will be used for the stated purpose wherever possible. If a campaign is overfunded or cannot be completed, we may reallocate remaining funds to a similar program in accordance with applicable law and our charitable mission.</li>
<li>We use third-party payment processors. By donating, you also agree to their applicable terms.</li>
<li>Tax deductibility of gifts may vary by country. U.S. donors should consult a tax professional. Receipts are provided for record-keeping but do not constitute tax advice.</li>
</ul>

<h2>4. Recurring Donations</h2>
<p>If you set up a recurring donation, you authorize us and our payment processor to charge your selected payment method at the interval you choose until you cancel. You may cancel recurring gifts by contacting us or through any self-service option we provide. Cancellations take effect for future charges only.</p>

<h2>5. Accounts and Communications</h2>
<p>If you create an account, you are responsible for safeguarding your login credentials and for all activity under your account. You agree to provide accurate information and to update it when it changes. By using our services, you consent to receive transactional emails (such as receipts and account notices). Marketing communications are sent only where permitted by law and may be opted out at any time.</p>

<h2>6. Acceptable Use</h2>
<p>You agree not to:</p>
<ul>
<li>Use our website for unlawful, fraudulent, or harmful purposes;</li>
<li>Attempt to gain unauthorized access to our systems or data;</li>
<li>Upload malware, spam, or misleading content through our forms;</li>
<li>Misrepresent your identity or affiliation with New Egypt Group;</li>
<li>Scrape, copy, or redistribute site content without written permission.</li>
</ul>

<h2>7. Intellectual Property</h2>
<p>All content on this website—including text, logos, images, videos, and campaign materials—is owned by New Egypt Group or used under license. You may share links to our pages for non-commercial purposes. Any other reproduction or commercial use requires our prior written consent.</p>

<h2>8. Beneficiary Stories and Media</h2>
<p>Stories, photos, and videos about beneficiaries are published with appropriate consent and in line with our safeguarding policies. You may not reuse beneficiary media outside the context in which we publish it without our permission.</p>

<h2>9. Third-Party Links</h2>
<p>Our website may link to third-party sites (such as payment providers or social networks). We are not responsible for the content, privacy practices, or terms of those external sites.</p>

<h2>10. Disclaimer of Warranties</h2>
<p>Our website and services are provided “as is” and “as available.” To the fullest extent permitted by law, we disclaim warranties of merchantability, fitness for a particular purpose, and non-infringement. We do not guarantee uninterrupted or error-free access to our platforms.</p>

<h2>11. Limitation of Liability</h2>
<p>To the maximum extent permitted by applicable law, New Egypt Group and its directors, officers, employees, and volunteers shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our website or services. Our total liability for any claim shall not exceed the amount you donated to us in the twelve (12) months preceding the claim, or one hundred U.S. dollars (USD $100), whichever is greater.</p>

<h2>12. Governing Law and Disputes</h2>
<p>These Terms are governed by the laws of the State in which New Egypt Group is registered in the United States, without regard to conflict-of-law principles. For users in Egypt, mandatory consumer protections under Egyptian law may also apply where they cannot be waived. Any dispute shall first be addressed through good-faith negotiation. If unresolved, disputes shall be submitted to the competent courts of our U.S. state of registration, unless applicable law requires otherwise.</p>

<h2>13. Changes to These Terms</h2>
<p>We may update these Terms from time to time. The “Last updated” date at the top of this page will reflect the latest revision. Continued use of our website after changes constitutes acceptance of the revised Terms.</p>

<h2>14. Contact</h2>
<p>Questions about these Terms may be sent through our <a href="/contact">Contact Us</a> page.</p>
HTML;
    }

    private function termsBodyAr(): string
    {
        return <<<'HTML'
<h2>1. الموافقة على الشروط</h2>
<p>مرحبًا بكم في مجموعة نيو إيجيبت («نحن» أو «المنظمة»). باستخدامك لموقعنا أو تقديم تبرع أو إنشاء حساب أو استخدام أي من خدماتنا، فإنك توافق على الالتزام بهذه الشروط والأحكام. إذا لم توافق، يُرجى عدم استخدام الموقع أو الخدمات.</p>
<p>مجموعة نيو إيجيبت منظمة غير ربحية تدعم المجتمعات المحتاجة في مصر وتتعامل مع المتبرعين والمتطوعين في الولايات المتحدة ودوليًا. تحكم هذه الشروط استخدامك لمنصاتنا الرقمية وعلاقتك معنا كمتبرع أو متطوع أو زائر.</p>

<h2>2. الأهلية</h2>
<p>يجب أن يكون عمرك 18 عامًا على الأقل، أو سن الرشد في بلدك، لتقديم تبرع أو إبرام اتفاق ملزم معنا. إذا كنت تتبرع نيابة عن منظمة، فإنك تقر بأن لديك الصلاحية لإلزامها.</p>

<h2>3. التبرعات</h2>
<p>جميع التبرعات عبر موقعنا أو القنوات المعتمدة هي هبات طوعية لدعم برامجنا الخيرية. ما لم يُذكر خلاف ذلك وقت التبرع:</p>
<ul>
<li>التبرعات غير قابلة للاسترداد عمومًا. إذا اعتقدت أن التبرع تم بالخطأ، تواصل معنا خلال 14 يومًا عبر صفحة اتصل بنا.</li>
<li>تُستخدم الهبات المخصصة للغرض المعلن قدر الإمكان. إذا تجاوزت الحملة هدفها أو تعذّر إكمالها، قد نُعيد توجيه الفائض لبرنامج مماثل وفق القانون ورسالتنا الخيرية.</li>
<li>نستخدم معالجي دفع من جهات خارجية، وتوافق على شروطهم عند التبرع.</li>
<li>قابلية الخصم الضريبي تختلف حسب البلد. يُنصح المتبرعون الأمريكيون باستشارة مختص ضريبي. الإيصالات للتوثيق وليست مشورة ضريبية.</li>
</ul>

<h2>4. التبرعات المتكررة</h2>
<p>عند إعداد تبرع متكرر، فإنك تفوّضنا ومعالج الدفع بخصم المبلغ وفق الفترة التي تختارها حتى الإلغاء. يمكنك الإلغاء بالتواصل معنا أو عبر أي خيار ذاتي نوفره. يسري الإلغاء على الخصومات المستقبلية فقط.</p>

<h2>5. الحسابات والتواصل</h2>
<p>إذا أنشأت حسابًا، فأنت مسؤول عن حماية بيانات الدخول وعن كل نشاط تحته. توافق على تقديم معلومات دقيقة وتحديثها عند تغيّرها. باستخدام خدماتنا، توافق على تلقي رسائل معاملات (مثل الإيصالات). الرسائل التسويقية تُرسل حيث يسمح القانون ويمكن إلغاء الاشتراك فيها.</p>

<h2>6. الاستخدام المقبول</h2>
<p>توافق على عدم:</p>
<ul>
<li>استخدام الموقع لأغراض غير قانونية أو احتيالية أو ضارة؛</li>
<li>محاولة الوصول غير المصرح به لأنظمتنا أو بياناتنا؛</li>
<li>رفع برمجيات خبيثة أو رسائل مزعجة أو محتوى مضلل عبر نماذجنا؛</li>
<li>انتحال هوية أو الإيهام بارتباطك بالمنظمة دون إذن؛</li>
<li>نسخ أو إعادة نشر محتوى الموقع دون إذن كتابي.</li>
</ul>

<h2>7. الملكية الفكرية</h2>
<p>جميع محتويات الموقع—نصوص وشعارات وصور وفيديوهات—مملوكة لمجموعة نيو إيجيبت أو مستخدمة بترخيص. يمكنك مشاركة روابط لأغراض غير تجارية. أي استخدام تجاري آخر يتطلب موافقتنا الكتابية.</p>

<h2>8. قصص المستفيدين والوسائط</h2>
<p>تُنشر قصص وصور ومقاطع المستفيدين بموافقة مناسبة وفق سياسات الحماية لدينا. لا يجوز إعادة استخدام وسائط المستفيدين خارج سياق نشرنا دون إذن.</p>

<h2>9. روابط جهات خارجية</h2>
<p>قد يحتوي موقعنا على روابط لمواقع خارجية (مثل مزودي الدفع أو الشبكات الاجتماعية). لسنا مسؤولين عن محتواها أو ممارسات الخصوصية أو شروطها.</p>

<h2>10. إخلاء المسؤولية</h2>
<p>يُقدَّم الموقع والخدمات «كما هي» و«حسب التوفر». إلى أقصى حد يسمح به القانون، نُخلي مسؤوليتنا عن ضمانات القابلية للتسويق والملاءمة لغرض معين وعدم الانتهاك. لا نضمن وصولًا دون انقطاع أو خالٍ من الأخطاء.</p>

<h2>11. تحديد المسؤولية</h2>
<p>إلى أقصى حد يسمح به القانون، لا تتحمل مجموعة نيو إيجيبت ومديروها وموظفوها ومتطوعوها مسؤولية أي أضرار غير مباشرة أو عرضية أو خاصة أو تبعية ناتجة عن استخدامك للموقع. إجمالي مسؤوليتنا عن أي مطالبة لا يتجاوز مبلغ تبرعاتك خلال الاثني عشر (12) شهرًا السابقة أو مائة دولار أمريكي، أيهما أكبر.</p>

<h2>12. القانون الحاكم والنزاعات</h2>
<p>تخضع هذه الشروط لقوانين الولاية الأمريكية التي سُجّلت فيها المنظمة، دون مراعاة تعارض القوانين. للمستخدمين في مصر، قد تنطبق حماية المستهلك الإلزامية بموجب القانون المصري حيث لا يمكن التنازل عنها. تُحل النزاعات أولًا بالتفاوض بحسن نية، وإلا تُعرض على المحاكم المختصة في ولاية تسجيلنا ما لم يفرض القانون غير ذلك.</p>

<h2>13. تعديل الشروط</h2>
<p>قد نُحدّث هذه الشروط من وقت لآخر. يعكس تاريخ «آخر تحديث» أعلى الصفحة أحدث مراجعة. استمرارك في استخدام الموقع بعد التعديل يعني قبول الشروط المحدّثة.</p>

<h2>14. التواصل</h2>
<p>للاستفسارات حول هذه الشروط، استخدم صفحة <a href="/contact">اتصل بنا</a>.</p>
HTML;
    }

    private function privacyBodyEn(): string
    {
        return <<<'HTML'
<h2>1. Introduction</h2>
<p>New Egypt Group (“we,” “us,” or “our”) respects your privacy. This Privacy Policy explains how we collect, use, disclose, and protect personal information when you visit our website, donate, volunteer, contact us, or otherwise interact with us. We operate primarily in the United States and Egypt and serve donors and supporters internationally.</p>
<p>By using our services, you acknowledge this Policy. If you do not agree, please discontinue use of our website and services.</p>

<h2>2. Information We Collect</h2>
<p>We may collect the following categories of information:</p>
<ul>
<li><strong>Identity and contact data:</strong> name, email address, phone number, mailing address, and country of residence.</li>
<li><strong>Donation data:</strong> donation amount, date, campaign designation, payment method type, and transaction identifiers. Full payment card numbers are processed by our payment providers and are not stored on our servers.</li>
<li><strong>Account data:</strong> username, password (hashed), preferences, and communication history if you register an account.</li>
<li><strong>Communications:</strong> messages you send via contact forms, email, or social channels.</li>
<li><strong>Technical data:</strong> IP address, browser type, device information, pages visited, and cookies (see Section 8).</li>
<li><strong>Volunteer and partnership data:</strong> skills, availability, organization name, and background-check information where required.</li>
</ul>

<h2>3. How We Use Your Information</h2>
<p>We use personal information to:</p>
<ul>
<li>Process donations and issue receipts;</li>
<li>Operate campaigns and communicate impact updates;</li>
<li>Respond to inquiries and provide customer support;</li>
<li>Manage volunteer and partnership applications;</li>
<li>Comply with legal, tax, and regulatory obligations;</li>
<li>Prevent fraud, abuse, and security incidents;</li>
<li>Improve our website and services through analytics;</li>
<li>Send newsletters and fundraising appeals where you have consented or where permitted by law.</li>
</ul>

<h2>4. Legal Bases for Processing (EEA/UK Visitors)</h2>
<p>Where the GDPR or similar laws apply, we process data based on: performance of a contract (e.g., processing your donation), legitimate interests (e.g., fraud prevention, organizational reporting), legal obligation, and consent (e.g., marketing emails).</p>

<h2>5. How We Share Information</h2>
<p>We do not sell your personal information. We may share data with:</p>
<ul>
<li><strong>Service providers:</strong> payment processors, email platforms, hosting providers, and analytics tools bound by confidentiality obligations;</li>
<li><strong>Professional advisers:</strong> auditors, lawyers, and accountants as needed;</li>
<li><strong>Partner organizations in Egypt:</strong> limited beneficiary or program data necessary to deliver aid, subject to safeguarding agreements;</li>
<li><strong>Authorities:</strong> when required by law, court order, or to protect rights and safety.</li>
</ul>
<p>International transfers from the U.S. or EEA to Egypt or other countries may occur. Where required, we implement appropriate safeguards such as standard contractual clauses or equivalent mechanisms.</p>

<h2>6. Data Retention</h2>
<p>We retain personal information only as long as necessary for the purposes described above, including legal and tax record-keeping (donation records are typically retained for at least seven years where required). Contact form messages are retained according to our operational needs and then deleted or anonymized.</p>

<h2>7. Security</h2>
<p>We implement administrative, technical, and organizational measures to protect your data, including encryption in transit (HTTPS), access controls, and staff training. No method of transmission over the Internet is 100% secure; we cannot guarantee absolute security.</p>

<h2>8. Cookies and Similar Technologies</h2>
<p>Our website uses cookies and similar technologies to remember preferences, maintain sessions, and understand how visitors use our site. You can control cookies through your browser settings. Disabling cookies may affect some site functionality.</p>

<h2>9. Your Rights</h2>
<p>Depending on your location, you may have the right to:</p>
<ul>
<li>Access, correct, or delete your personal information;</li>
<li>Object to or restrict certain processing;</li>
<li>Withdraw consent where processing is consent-based;</li>
<li>Request portability of data you provided;</li>
<li>Lodge a complaint with a supervisory authority.</li>
</ul>
<p>To exercise these rights, contact us via the <a href="/contact">Contact Us</a> page. We will respond within the timeframe required by applicable law.</p>

<h2>10. Children’s Privacy</h2>
<p>Our website is not directed at children under 13 (or 16 in certain jurisdictions). We do not knowingly collect personal information from children. Beneficiary children may appear in program stories with guardian consent; such content is managed under our safeguarding policy.</p>

<h2>11. Third-Party Services</h2>
<p>Our site may embed content or links from third parties (e.g., payment gateways, YouTube, social media). Their privacy practices are governed by their own policies. We encourage you to review them before providing personal information.</p>

<h2>12. Changes to This Policy</h2>
<p>We may update this Privacy Policy periodically. Material changes will be posted on this page with an updated effective date. We encourage you to review this Policy regularly.</p>

<h2>13. Contact Us</h2>
<p>For privacy-related questions or requests, please use our <a href="/contact">Contact Us</a> form and include “Privacy Request” in the subject line.</p>
HTML;
    }

    private function privacyBodyAr(): string
    {
        return <<<'HTML'
<h2>1. مقدمة</h2>
<p>تحترم مجموعة نيو إيجيبت («نحن») خصوصيتك. توضّح سياسة الخصوصية هذه كيف نجمع معلوماتك الشخصية ونستخدمها ونفصح عنها ونحميها عند زيارة موقعنا أو التبرع أو التطوع أو التواصل معنا. نعمل أساسًا في الولايات المتحدة ومصر ونخدم المتبرعين والداعمين دوليًا.</p>
<p>باستخدامك لخدماتنا، فإنك تقر بهذه السياسة. إذا لم توافق، يُرجى التوقف عن استخدام الموقع والخدمات.</p>

<h2>2. المعلومات التي نجمعها</h2>
<p>قد نجمع الفئات التالية:</p>
<ul>
<li><strong>بيانات الهوية والتواصل:</strong> الاسم والبريد الإلكتروني والهاتف والعنوان وبلد الإقامة.</li>
<li><strong>بيانات التبرع:</strong> المبلغ والتاريخ والحملة ونوع وسيلة الدفع ومعرّفات المعاملة. أرقام البطاقات الكاملة تُعالَج عبر مزودي الدفع ولا تُخزَّن على خوادمنا.</li>
<li><strong>بيانات الحساب:</strong> اسم المستخدم وكلمة المرور (مشفّرة) والتفضيلات وسجل التواصل إن سجّلت حسابًا.</li>
<li><strong>المراسلات:</strong> الرسائل عبر نماذج التواصل أو البريد أو القنوات الاجتماعية.</li>
<li><strong>البيانات التقنية:</strong> عنوان IP ونوع المتصفح والجهاز والصفحات التي تمت زيارتها وملفات تعريف الارتباط (انظر القسم 8).</li>
<li><strong>بيانات التطوع والشراكة:</strong> المهارات والتوفر واسم المنظمة وبيانات الفحص الخلفي عند الحاجة.</li>
</ul>

<h2>3. كيف نستخدم معلوماتك</h2>
<p>نستخدم البيانات الشخصية من أجل:</p>
<ul>
<li>معالجة التبرعات وإصدار الإيصالات؛</li>
<li>تشغيل الحملات والتواصل بتحديثات الأثر؛</li>
<li>الرد على الاستفسارات وتقديم الدعم؛</li>
<li>إدارة طلبات التطوع والشراكة؛</li>
<li>الامتثال للالتزامات القانونية والضريبية؛</li>
<li>منع الاحتيال وإساءة الاستخدام والحوادث الأمنية؛</li>
<li>تحسين الموقع والخدمات عبر التحليلات؛</li>
<li>إرسال النشرات والحملات التمويلية حيث وافقت أو يسمح القانون.</li>
</ul>

<h2>4. الأسس القانونية للمعالجة (زوار الاتحاد الأوروبي/المملكة المتحدة)</h2>
<p>حيث ينطبق اللائحة العامة لحماية البيانات أو قوانين مماثلة، نعالج البيانات بناءً على: تنفيذ عقد (مثل معالجة تبرعك)، مصالح مشروعة (مثل منع الاحتيال)، التزام قانوني، والموافقة (مثل رسائل التسويق).</p>

<h2>5. مشاركة المعلومات</h2>
<p>لا نبيع معلوماتك الشخصية. قد نشارك البيانات مع:</p>
<ul>
<li><strong>مقدمي الخدمات:</strong> معالجي الدفع والبريد والاستضافة والتحليلات الملزمين بالسرية؛</li>
<li><strong>المستشارين المهنيين:</strong> مدققي الحسابات والمحامين والمحاسبين عند الحاجة؛</li>
<li><strong>الشركاء في مصر:</strong> بيانات محدودة عن البرامج أو المستفيدين اللازمة لتقديم المساعدة وفق اتفاقيات الحماية؛</li>
<li><strong>الجهات الرسمية:</strong> عندما يفرض القانون أو أمر قضائي أو لحماية الحقوق والسلامة.</li>
</ul>
<p>قد تحدث عمليات نقل دولية من الولايات المتحدة أو الاتحاد الأوروبي إلى مصر أو دول أخرى. حيث يُطلب، نطبّق ضمانات مناسبة مثل البنود التعاقدية النموذجية.</p>

<h2>6. الاحتفاظ بالبيانات</h2>
<p>نحتفظ بالبيانات فقط للمدة اللازمة للأغراض المذكورة، بما في ذلك السجلات القانونية والضريبية (سجلات التبرع تُحفظ عادة سبع سنوات على الأقل حيث يُطلب). تُحفظ رسائل التواصل وفق احتياجاتنا التشغيلية ثم تُحذف أو تُجهّل.</p>

<h2>7. الأمان</h2>
<p>نطبّق تدابير إدارية وتقنية وتنظيمية لحماية بياناتك، بما في ذلك التشفير أثناء النقل (HTTPS) وضوابط الوصول وتدريب الموظفين. لا توجد طريقة نقل عبر الإنترنت آمنة بنسبة 100%؛ لا يمكننا ضمان أمان مطلق.</p>

<h2>8. ملفات تعريف الارتباط</h2>
<p>يستخدم موقعنا ملفات تعريف الارتباط وتقنيات مشابهة لتذكر التفضيلات والحفاظ على الجلسات وفهم استخدام الزوار. يمكنك التحكم عبر إعدادات المتصفح. تعطيلها قد يؤثر على بعض وظائف الموقع.</p>

<h2>9. حقوقك</h2>
<p>حسب موقعك، قد يكون لك الحق في:</p>
<ul>
<li>الوصول إلى بياناتك أو تصحيحها أو حذفها؛</li>
<li>الاعتراض على معالجة معيّنة أو تقييدها؛</li>
<li>سحب الموافقة حيث تكون المعالجة قائمة عليها؛</li>
<li>طلب نقل البيانات التي قدمتها؛</li>
<li>تقديم شكوى إلى جهة رقابية.</li>
</ul>
<p>لممارسة هذه الحقوق، تواصل معنا عبر <a href="/contact">اتصل بنا</a>. سنرد ضمن المدة التي يفرضها القانون المعمول به.</p>

<h2>10. خصوصية الأطفال</h2>
<p>موقعنا غير موجّه للأطفال دون 13 عامًا (أو 16 في بعض الدول). لا نجمع عن قصد بيانات أطفال. قد يظهر أطفال مستفيدون في قصص البرامج بموافقة ولي الأمر وفق سياسة الحماية لدينا.</p>

<h2>11. خدمات جهات خارجية</h2>
<p>قد يتضمن الموقع محتوى أو روابط لجهات خارجية (بوابات دفع، يوتيوب، شبكات اجتماعية). ممارسات الخصوصية لديها تخضع لسياساتها. ننصح بمراجعتها قبل تقديم معلومات شخصية.</p>

<h2>12. تعديل هذه السياسة</h2>
<p>قد نُحدّث سياسة الخصوصية دوريًا. تُنشر التغييرات الجوهرية على هذه الصفحة مع تاريخ سريان محدّث. ننصح بمراجعتها بانتظام.</p>

<h2>13. التواصل معنا</h2>
<p>للاستفسارات أو الطلبات المتعلقة بالخصوصية، استخدم <a href="/contact">اتصل بنا</a> مع ذكر «طلب خصوصية» في عنوان الرسالة.</p>
HTML;
    }
}
