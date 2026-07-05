<!-- resources/js/pages/PrivacyPolicy.vue -->
<script setup lang="ts">
import { usePageAnimation } from '@/composables/usePageAnimation'
import Head from '@/components/layouts/Head.vue'
import Navbar from '@/components/layouts/Navbar.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyFooter from '@/components/layouts/MyFooter.vue'
import Breadcrumbs from '@/components/layouts/Breadcrumbs.vue'

const { animClass } = usePageAnimation()

const breadcrumbPages = [
  { name: 'Privacy Policy', href: '/privacy', current: true },
]

const pageMeta = {
  title: 'Privacy Policy',
  description:
    'How musicExams.help (centre 120) collects, uses and protects your data under UK GDPR: newsletter sign-ups, teacher records, student exam data and your rights.',
  canonicalUrl: 'https://musicexams.help/privacy',
}

const lastUpdated = '10 May 2026'
</script>

<template>
  <Head :title="pageMeta.title" :description="pageMeta.description" :canonical-url="pageMeta.canonicalUrl" />

  <div class="min-h-screen bg-black text-brand-text">
    <Navbar />

    <!-- HEADER -->
    <section class="bg-brand-surface pt-36 pb-10 md:pt-40 lg:pt-40">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="mb-6">
          <Breadcrumbs :pages="breadcrumbPages" home-href="/" />
        </div>
        <div class="text-center">
          <div :class="animClass('fade-up', 0)">
            <MyTextConstructor variant="eyebrow" alignment="center" spacing="tight">
              <template #myTitle>Legal</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 1)">
            <MyTextConstructor
              variant="heading"
              fontFamily="display"
              alignment="center"
              spacing="tight"
              titleTag="h1"
              class="mt-3 md:!text-3xl lg:!text-4xl"
            >
              <template #myTitle>Privacy Policy</template>
            </MyTextConstructor>
          </div>

          <div :class="animClass('fade-up', 2)">
            <p class="mx-auto mt-4 max-w-2xl text-base text-brand-text-soft sm:text-base md:text-lg lg:text-xl">
              Last updated: {{ lastUpdated }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CONTENT -->
    <section class="border-t border-brand-border bg-brand-bg">
      <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div :class="animClass('fade-up', 2)" class="prose-policy space-y-8">

          <!-- INTRO -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Who we are</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              musicExams.help is operated by Paul Sheridan, a sole trader trading as Red Hot Music, based in Wirral, England.
              We are a Trinity College London Registered Public Exam Centre (centre 120) for music examinations.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              musicExams.help is registered with the UK Information Commissioner's Office as an independent Data Controller
              (registration <a href="https://ico.org.uk/ESDWebPages/Entry/ZC143303" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">ZC143303</a>).
              For the purposes of UK GDPR, both musicExams.help and Trinity College London
              act as independent Data Controllers in respect of exam-related personal data.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              If you have questions about this policy, please contact us at
              <a href="mailto:musicexams@musicexams.help" class="font-medium text-brand-accent hover:underline">musicexams@musicexams.help</a>.
            </p>
          </div>

          <!-- WHAT WE COLLECT -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">What personal data we collect</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              We collect different types of information depending on how you interact with our site:
            </p>
            <p class="mt-3 text-base font-semibold text-brand-text sm:text-base md:text-lg">Newsletter and email sign-up</p>
            <p class="mt-1 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              When you sign up to our mailing list, we collect your name, email address and whether you are a teacher or a parent/student.
              You may optionally provide additional details such as instrument family or school name.
            </p>
            <p class="mt-3 text-base font-semibold text-brand-text sm:text-base md:text-lg">Teacher information</p>
            <p class="mt-1 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              If you are a music teacher working with centre 120, we may hold your name, email address, phone number,
              school affiliations, instruments taught, and a record of exam entries made through the centre.
              This information helps us provide support and administer the rewards and recognition programme.
            </p>
            <p class="mt-3 text-base font-semibold text-brand-text sm:text-base md:text-lg">Augmentation from public sources</p>
            <p class="mt-1 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              For contacts who interact with us — teachers, parents, and students booking exams — we may augment our records
              with publicly available professional information (for example school affiliation, instrument specialism, or
              location) drawn from sources such as professional networking sites and our customer relationship management
              platform's enrichment service. We do this to provide better service and to keep our records accurate. This
              data is held under <span class="font-semibold">legitimate interest</span> and is not shared with third parties
              for marketing purposes. You can request to see, correct, or delete the augmented information held on your
              record at any time by emailing <a href="mailto:musicexams@musicexams.help" class="font-medium text-brand-accent hover:underline">musicexams@musicexams.help</a>.
            </p>
            <p class="mt-3 text-base font-semibold text-brand-text sm:text-base md:text-lg">Student and exam data</p>
            <p class="mt-1 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              We process student names, instruments, grades and exam results submitted through centre 120.
              Where students are under 18, we treat this data with additional care (see "Children's data" below).
              On public-facing pages such as our <strong>Hall of Fame</strong>, we display anonymised information only (e.g. "Fred B — Trumpet, Grade 3, Distinction").
              A student's full name will only appear publicly if a parent or guardian has explicitly opted in via their account.
            </p>
            <p class="mt-3 text-base font-semibold text-brand-text sm:text-base md:text-lg">Website usage data</p>
            <p class="mt-1 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              If you consent to analytics cookies, we use Google Analytics (GA4) to collect anonymised usage data such as
              pages visited, time spent on the site, and how you found us. This data does not personally identify you.
              See our <a href="/cookies" class="font-medium text-brand-accent hover:underline">Cookie Policy</a> for full details.
            </p>
          </div>

          <!-- WHY WE COLLECT IT -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Why we collect your data (lawful basis)</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Under UK GDPR, we must have a lawful basis for processing your personal data. We rely on the following:
            </p>
            <p class="mt-3 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">Consent</span> — for newsletter sign-ups, analytics cookies, and displaying a child's full name publicly.
              You can withdraw consent at any time by contacting us or using the unsubscribe link in any email.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">Legitimate interest</span> — for managing teacher relationships, administering exam entries through centre 120,
              and running the rewards and recognition programme. We have a genuine business need for this data and have
              assessed that it does not override your rights.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">Contract</span> — where we are providing a service to you as part of the exam booking and administration process.
            </p>
          </div>

          <!-- CHILDREN'S DATA -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Children's data</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Many of our exam candidates are under 18. We take extra care with children's data:
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Student results on public pages (such as the <strong>Hall of Fame</strong>) are displayed using first name and initial only by default —
              for example "Fred B". No child's full name appears publicly unless a parent or guardian has logged in and explicitly
              opted in. This consent can be withdrawn at any time.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              We do not knowingly collect personal information directly from children. Exam data is submitted by teachers
              or parents on behalf of students through the official Trinity College London booking system.
            </p>
          </div>

          <!-- DATA SHARING -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Who we share data with</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">Trinity College London</span> — as the awarding organisation for the exams we administer.
              We share candidate enrolment, exam booking, and result data with Trinity in our role as a Registered Public Exam Centre.
              Trinity acts as an independent Data Controller for that data. Trinity's privacy statements are available at
              <a href="https://www.trinitycollege.com/page/privacy" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">trinitycollege.com/page/privacy</a>.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">Google</span> — if you consent to analytics cookies, anonymised usage data is processed by Google Analytics.
              Google's privacy policy is available at
              <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">policies.google.com/privacy</a>.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">Laravel Cloud</span> — our website is hosted on Laravel Cloud infrastructure. Data is stored securely on their servers.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">HubSpot</span> — we use HubSpot as our customer relationship management (CRM) and email marketing platform.
              Contact information for teachers, parents and students is stored on HubSpot's secure servers, and HubSpot's
              enrichment service may augment records with publicly available professional information (see "Augmentation
              from public sources" above). HubSpot's privacy policy is available at
              <a href="https://legal.hubspot.com/privacy-policy" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">legal.hubspot.com/privacy-policy</a>.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              <span class="font-semibold">Advertising platforms (Meta, Google)</span> — we may share your email address (and where available, your name),
              in hashed form, with online advertising platforms such as Meta and Google, to help them identify their users who have a relationship
              with musicExams.help and to help us reach other people with similar professional interests in music teaching and Trinity exams.
              Hashing means your details are converted into a one-way encrypted string before they leave our systems, so the platforms cannot read
              your actual contact information. We rely on our <span class="font-semibold">legitimate interest</span> as the lawful basis for this
              processing under Article 6(1)(f) of the UK GDPR — specifically, our interest in promoting Trinity exam services to music teachers
              who are likely to value them. You can opt out of this processing at any time by clicking the unsubscribe link in any email we send
              you, or by emailing us at
              <a href="mailto:musicexams@musicexams.help" class="font-medium text-brand-accent hover:underline">musicexams@musicexams.help</a>.
              Once you opt out, we will remove your details from any active advertising audiences within a reasonable period (typically the next
              scheduled audience refresh).
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              We do not sell your personal data to anyone. Other than the advertising-platform processing described above, we do not share
              your information with third parties for marketing purposes.
            </p>
          </div>

          <!-- DATA RETENTION -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">How long we keep your data</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Newsletter subscriber data is kept for as long as you remain subscribed. You can unsubscribe at any time.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Teacher and exam records are kept for as long as they are needed to administer the centre and rewards programme.
              Exam result data may be retained indefinitely for verification purposes, in line with Trinity College London's
              own data retention policy.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              If you ask us to delete your data, we will do so within 30 days unless we have a legal obligation to retain it.
            </p>
          </div>

          <!-- YOUR RIGHTS -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Your rights</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              Under UK GDPR, you have the right to: access the personal data we hold about you; ask us to correct inaccurate data;
              ask us to delete your data (where we have no legal reason to keep it); withdraw consent at any time;
              object to processing based on legitimate interest; and request a copy of your data in a portable format.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              To exercise any of these rights, email us at
              <a href="mailto:musicexams@musicexams.help" class="font-medium text-brand-accent hover:underline">musicexams@musicexams.help</a>.
              We will respond within 30 days.
            </p>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              If you are not satisfied with our response, you have the right to complain to the Information Commissioner's Office (ICO) at
              <a href="https://ico.org.uk" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-accent hover:underline">ico.org.uk</a>.
            </p>
          </div>

          <!-- CHANGES -->
          <div>
            <h2 class="text-lg font-bold text-brand-primary sm:text-xl">Changes to this policy</h2>
            <p class="mt-2 text-base leading-relaxed text-brand-text sm:text-base md:text-lg">
              We may update this policy from time to time. Any changes will be posted on this page with an updated date.
              If we make significant changes that affect how we use your data, we will notify you by email where possible.
            </p>
          </div>

        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <MyFooter variant="gradient" />
  </div>
</template>
