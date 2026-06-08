<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Seeds real-world FAQ entries for an international charity operating in the US and Egypt.
 * Idempotent: keyed on English question text via updateOrCreate.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faqs() as $index => $faq) {
            Faq::updateOrCreate(
                ['question_en' => $faq['question_en']],
                [
                    'question_ar' => $faq['question_ar'],
                    'answer_ar' => $faq['answer_ar'],
                    'answer_en' => $faq['answer_en'],
                    'sort_order' => $index + 1,
                    'is_published' => true,
                ]
            );
        }

        $this->command->info('✅ FAQs seeded: '.Faq::count().' entries.');
    }

    /**
     * @return list<array{question_ar: string, question_en: string, answer_ar: string, answer_en: string}>
     */
    private function faqs(): array
    {
        return [
            [
                'question_en' => 'What is New Egypt Group and what does the organization do?',
                'question_ar' => 'ما هي مجموعة نيو إيجيبت وماذا تفعل المنظمة؟',
                'answer_en' => '<p>New Egypt Group is a registered non-profit organization that connects the Egyptian diaspora in the United States with communities in need across Egypt. We run programs in education, healthcare, food security, orphan sponsorship, and emergency relief. Our work is carried out through verified local partners, field teams, and transparent financial reporting.</p>',
                'answer_ar' => '<p>مجموعة نيو إيجيبت منظمة غير ربحية مسجلة تربط الجالية المصرية في الولايات المتحدة بالمجتمعات المحتاجة في مصر. ننفّذ برامج في التعليم والرعاية الصحية والأمن الغذائي وكفالة الأيتام والإغاثة الطارئة. يتم تنفيذ عملنا عبر شركاء محليين موثوقين وفرق ميدانية وتقارير مالية شفافة.</p>',
            ],
            [
                'question_en' => 'How can I make a donation?',
                'question_ar' => 'كيف يمكنني التبرع؟',
                'answer_en' => '<p>You can donate securely through our website by selecting an active campaign or making a general donation. We accept major credit and debit cards and other payment methods displayed at checkout. You may also donate by bank transfer or check—contact us for wire instructions and mailing details. All online donations receive an email confirmation immediately after payment.</p>',
                'answer_ar' => '<p>يمكنك التبرع بأمان عبر موقعنا باختيار حملة نشطة أو تقديم تبرع عام. نقبل بطاقات الائتمان والخصم الرئيسية وطرق الدفع المعروضة عند إتمام العملية. يمكنك أيضًا التبرع عبر تحويل بنكي أو شيك—تواصل معنا للحصول على تعليمات التحويل وعنوان البريد. يتلقى جميع المتبرعين عبر الإنترنت رسالة تأكيد بالبريد الإلكتروني فور إتمام الدفع.</p>',
            ],
            [
                'question_en' => 'Are donations tax-deductible in the United States?',
                'question_ar' => 'هل التبرعات معفاة من الضرائب في الولايات المتحدة؟',
                'answer_en' => '<p>Donations to New Egypt Group may be tax-deductible to the extent permitted by U.S. law. We will provide a receipt for your records. Please consult your tax advisor regarding your specific situation. Our Employer Identification Number (EIN) is available upon request for tax-filing purposes.</p>',
                'answer_ar' => '<p>قد تكون التبرعات لمجموعة نيو إيجيبت قابلة للخصم الضريبي وفقًا للقانون الأمريكي. نوفّر إيصالًا لسجلاتك. يُرجى استشارة مستشارك الضريبي بخصوص وضعك. رقم التعريف الضريبي للمنظمة (EIN) متاح عند الطلب لأغراض الإقرار الضريبي.</p>',
            ],
            [
                'question_en' => 'Where does my donation go?',
                'question_ar' => 'أين يذهب تبرعي؟',
                'answer_en' => '<p>Unless you designate your gift to a specific campaign, donations are allocated to our highest-priority programs based on assessed community needs. Campaign-specific donations are applied entirely to that program, minus standard payment processing fees. We publish annual impact summaries and financial overviews so donors can see how funds are used in Egypt and through our U.S. operations.</p>',
                'answer_ar' => '<p>ما لم تُخصّص تبرعك لحملة معيّنة، تُوجَّه التبرعات إلى برامجنا الأكثر إلحاحًا وفق تقييم احتياجات المجتمع. تُطبَّق التبرعات المخصصة لحملة بالكامل على تلك الحملة بعد خصم رسوم معالجة الدفع المعتادة. ننشر ملخصات سنوية للأثر والشفافية المالية ليتمكن المتبرعون من معرفة كيفية استخدام الأموال في مصر وعبر عملياتنا في الولايات المتحدة.</p>',
            ],
            [
                'question_en' => 'Can I sponsor a specific child, family, or beneficiary?',
                'question_ar' => 'هل يمكنني كفالة طفل أو أسرة أو مستفيد محدد؟',
                'answer_en' => '<p>Yes. Our orphan and family sponsorship programs match donors with vetted beneficiaries. Sponsorship typically covers education, healthcare, and basic living support on a monthly or annual basis. You will receive periodic updates about your sponsored beneficiary in accordance with our privacy and child-protection policies. Contact our team to learn about current sponsorship opportunities.</p>',
                'answer_ar' => '<p>نعم. برامج كفالة الأيتام والأسر لدينا تربط المتبرعين بمستفيدين تم التحقق منهم. تغطي الكفالة عادة التعليم والرعاية الصحية والدعم المعيشي الأساسي شهريًا أو سنويًا. ستتلقى تحديثات دورية عن المستفيد الذي تكفله وفق سياسات الخصوصية وحماية الطفل لدينا. تواصل مع فريقنا للاطلاع على فرص الكفالة الحالية.</p>',
            ],
            [
                'question_en' => 'Do you accept Zakat or Sadaqah donations?',
                'question_ar' => 'هل تقبلون تبرعات الزكاة أو الصدقة؟',
                'answer_en' => '<p>We accept Zakat and Sadaqah where program eligibility aligns with Islamic guidelines. Zakat-eligible funds are tracked separately and disbursed only to beneficiaries who meet the criteria reviewed by our Sharia advisory process. When donating, please indicate if your gift is Zakat so we can apply it correctly. For questions about eligibility, email us before giving.</p>',
                'answer_ar' => '<p>نقبل الزكاة والصدقة عندما تتوافق برامجنا مع الضوابط الشرعية. تُتابَع أموال الزكاة بشكل منفصل وتُصرف فقط للمستفيدين المؤهلين وفق معايير يراجعها مستشارونا الشرعيون. عند التبرع، يُرجى الإشارة إلى أن التبرع زكاة لنطبّقه بشكل صحيح. للاستفسار عن الأهلية، راسلنا قبل التبرع.</p>',
            ],
            [
                'question_en' => 'Can I donate from outside the United States or Egypt?',
                'question_ar' => 'هل يمكنني التبرع من خارج الولايات المتحدة أو مصر؟',
                'answer_en' => '<p>Yes. Donors from many countries can give online using internationally accepted cards. For large gifts or corporate giving from outside the U.S., contact us for tailored transfer options. Currency is processed in U.S. dollars unless otherwise stated. Egyptian residents may also support certain programs through local channels—reach out for current options.</p>',
                'answer_ar' => '<p>نعم. يمكن للمتبرعين من دول عديدة التبرع عبر الإنترنت باستخدام بطاقات مقبولة دوليًا. للتبرعات الكبيرة أو الشركات من خارج الولايات المتحدة، تواصل معنا لخيارات تحويل مناسبة. تُعالَج العملة بالدولار الأمريكي ما لم يُذكر خلاف ذلك. يمكن للمقيمين في مصر دعم بعض البرامج عبر قنوات محلية—راسلنا لمعرفة الخيارات الحالية.</p>',
            ],
            [
                'question_en' => 'How do I get a donation receipt?',
                'question_ar' => 'كيف أحصل على إيصال تبرع؟',
                'answer_en' => '<p>An email receipt is sent automatically after every successful online donation. If you need a duplicate receipt, an annual giving statement, or a receipt for an offline gift, contact us with your full name, donation date, and amount. Please allow a few business days for manual requests to be processed.</p>',
                'answer_ar' => '<p>يُرسل إيصال بالبريد الإلكتروني تلقائيًا بعد كل تبرع ناجح عبر الإنترنت. إذا احتجت نسخة مكررة أو كشف تبرعات سنوي أو إيصالًا لتبرع خارج الموقع، تواصل معنا مع اسمك الكامل وتاريخ ومبلغ التبرع. يُرجى السماح ببضعة أيام عمل لمعالجة الطلبات اليدوية.</p>',
            ],
            [
                'question_en' => 'How does New Egypt Group select beneficiaries?',
                'question_ar' => 'كيف تختار مجموعة نيو إيجيبت المستفيدين؟',
                'answer_en' => '<p>Beneficiaries are identified through field assessments, community referrals, and partner organizations. Each case is reviewed against program criteria covering need, documentation, and feasibility of support. We prioritize orphans, low-income families, patients with critical medical needs, and communities affected by emergencies. Decisions are documented and subject to periodic re-assessment.</p>',
                'answer_ar' => '<p>يُحدَّد المستفيدون عبر تقييمات ميدانية وإحالات مجتمعية ومنظمات شريكة. تُراجع كل حالة وفق معايير البرنامج من حيث الحاجة والوثائق وإمكانية تقديم الدعم. نعطي أولوية للأيتام والأسر محدودة الدخل والمرضى ذوي الاحتياجات الطبية الحرجة والمجتمعات المتضررة من الطوارئ. تُوثَّق القرارات وتُعاد مراجعتها دوريًا.</p>',
            ],
            [
                'question_en' => 'Can I volunteer with New Egypt Group?',
                'question_ar' => 'هل يمكنني التطوع مع مجموعة نيو إيجيبت؟',
                'answer_en' => '<p>We welcome volunteers in the United States and Egypt for fundraising events, administrative support, translation, medical missions, and community outreach. Some roles require background checks or professional credentials. Submit a volunteer inquiry through our contact form and tell us about your skills, location, and availability. Our team will follow up when a suitable opportunity arises.</p>',
                'answer_ar' => '<p>نرحب بالمتطوعين في الولايات المتحدة ومصر لفعاليات جمع التبرعات والدعم الإداري والترجمة والبعثات الطبية والتواصل المجتمعي. بعض الأدوار تتطلب فحوصات خلفية أو مؤهلات مهنية. أرسل استفسار تطوع عبر نموذج التواصل مع ذكر مهاراتك وموقعك وتوفرك. سيتواصل معك فريقنا عند توفر فرصة مناسبة.</p>',
            ],
            [
                'question_en' => 'How can my organization partner with New Egypt Group?',
                'question_ar' => 'كيف يمكن لمنظمتي الشراكة مع مجموعة نيو إيجيبت؟',
                'answer_en' => '<p>We partner with mosques, churches, schools, clinics, corporations, and other NGOs on co-funded projects, in-kind donations, and awareness campaigns. Partnership proposals should include your organization’s registration details, proposed scope, geography, and timeline. Email us through the contact page with the subject line “Partnership Inquiry” and our programs team will respond.</p>',
                'answer_ar' => '<p>نتشارك مع المساجد والكنائس والمدارس والعيادات والشركات والمنظمات غير الحكومية في مشاريع مشتركة التمويل وتبرعات عينية وحملات توعية. يجب أن تشمل مقترحات الشراكة بيانات تسجيل منظمتكم ونطاق العمل والجغرافيا والجدول الزمني. راسلنا عبر صفحة التواصل بعنوان “استفسار شراكة” وسيرد فريق البرامج.</p>',
            ],
            [
                'question_en' => 'How do I contact New Egypt Group?',
                'question_ar' => 'كيف أتواصل مع مجموعة نيو إيجيبت؟',
                'answer_en' => '<p>Use the <a href="/contact">Contact Us</a> form on our website for general inquiries, sponsorship questions, partnership requests, or media requests. We aim to respond within two to three business days. For urgent humanitarian matters related to an active campaign, please include “Urgent” in your subject line.</p>',
                'answer_ar' => '<p>استخدم نموذج <a href="/contact">اتصل بنا</a> على موقعنا للاستفسارات العامة أو الكفالة أو الشراكات أو الإعلام. نهدف للرد خلال يومي إلى ثلاثة أيام عمل. للحالات الإنسانية العاجلة المرتبطة بحملة نشطة، يُرجى تضمين كلمة «عاجل» في عنوان الرسالة.</p>',
            ],
        ];
    }
}
