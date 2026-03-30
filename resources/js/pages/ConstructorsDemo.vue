<!-- resources/js/pages/ConstructorsDemo.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/components/layouts/AppLayout.vue'
import Head from '@/components/layouts/Head.vue'
import PageHeader from '@/components/reusables/PageHeader.vue'
import MyTextConstructor from '@/components/reusables/MyTextConstructor.vue'
import MyButtonConstructor from '@/components/reusables/MyButtonConstructor.vue'
import MyAlert from '@/components/reusables/MyAlert.vue'
import MyInputConstructor from '@/components/reusables/MyInputConstructor.vue'
import MyTextareaConstructor from '@/components/reusables/MyTextareaConstructor.vue'
import MyProgress from '@/components/reusables/MyProgress.vue'
import MyTableConstructor from '@/components/reusables/MyTableConstructor.vue'
import MyAccordionConstructor from '@/components/reusables/MyAccordionConstructor.vue'
import MyBanner from '@/components/reusables/MyBanner.vue'

import { BookOpenIcon, MusicalNoteIcon, TrophyIcon } from '@heroicons/vue/24/outline'
import MyRunnerConstructor from '@/components/reusables/MyRunnerConstructor.vue'

const runnerTextItems = [
  {
    id: 1,
    title: 'Go to official booking',
    subTitle: 'Continue to the Trinity booking route when you are ready.',
    url: 'https://booking.trinitycollege.com/?larCode=120',
  },
  {
    id: 2,
    title: 'Why use code 120',
    subTitle: 'See why keeping the centre code visible matters before booking.',
    url: '#',
  },
  {
    id: 3,
    title: 'Incentives and recognition',
    subTitle: 'See how code 120 may later connect to draws, recognition and Hall of Fame features.',
    url: '#',
  },
]

const runnerIconItems = [
  {
    id: 1,
    title: 'Simple booking path',
    subTitle: 'Less confusion',
    descript: 'A clearer route through the Trinity booking process for teachers, parents and students.',
    icon: BookOpenIcon,
  },
  {
    id: 2,
    title: 'Music exam guidance',
    subTitle: 'Keep code 120 visible',
    descript: 'Make the centre code easier to remember and use at the right point in the process.',
    icon: MusicalNoteIcon,
  },
  {
    id: 3,
    title: 'Quarterly rewards',
    subTitle: 'Recognition matters',
    descript: 'Celebrate students and teachers with prize draws, shout-outs and Hall of Fame ideas.',
    icon: TrophyIcon,
  },
]

const runnerImageItems = [
  {
    id: 1,
    title: 'Trinity booking help',
    subTitle: 'Step-by-step support',
    paragraph: 'Clear guidance before going through to the official booking page.',
    image: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/logo.png',
    header: 'Featured',
    footer: 'Learn More',
  },
  {
    id: 2,
    title: 'Rewards and recognition',
    subTitle: 'Code 120 incentives',
    paragraph: 'Reward effort with quarterly prize draws and recognition ideas.',
    image: '/images/rewards_add.png',
    header: 'Rewards',
    footer: 'See Incentives',
  },
  {
    id: 3,
    title: 'Teachers and students',
    subTitle: 'Built to support both',
    paragraph: 'Designed to help, not compete with, teachers.',
    image: 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Trinity_Centre_120_logo_purple.png',
    header: 'Support',
    footer: 'Read More',
  },
]

const handleRunnerClick = (card: { url?: string; isExternal?: boolean }) => {
  if (!card.url) return

  if (card.url.startsWith('http')) {
    window.open(card.url, '_blank', 'noopener,noreferrer')
    return
  }

  if (card.url === '#') return
}

const inputValue = ref('')
const emailValue = ref('')
const textareaValue = ref('This is a textarea demo using your new constructor system.')

const progressBasic = ref(35)
const progressSuccess = ref(72)
const progressWarning = ref(54)
const progressDanger = ref(88)

const tableColumns = [
  { key: 'name', title: 'Student Name', sortable: true },
  { key: 'instrument', title: 'Instrument', sortable: true },
  { key: 'grade', title: 'Grade', sortable: true, align: 'center' as const },
  { key: 'status', title: 'Status', sortable: true },
]

const tableData = [
  { name: 'Emily Turner', instrument: 'Piano', grade: 5, status: 'Booked' },
  { name: 'Jack Whitfield', instrument: 'Violin', grade: 3, status: 'Pending' },
  { name: 'Sophie Reeves', instrument: 'Flute', grade: 7, status: 'Booked' },
  { name: 'Oliver Chen', instrument: 'Guitar', grade: 2, status: 'Cancelled' },
  { name: 'Amara Osei', instrument: 'Voice', grade: 6, status: 'Booked' },
]

const accordionItems = [
  { id: 1, question: 'What is centre code 120?', answer: 'Centre code 120 is the unique identifier for Music Exams Help when booking Trinity College London exams. It ensures your booking is linked to our support system.' },
  { id: 2, question: 'How do I book a Trinity exam?', answer: 'Visit the official Trinity booking page, enter your details, and make sure centre code 120 is visible. We provide step-by-step guidance to make the process easier.' },
  { id: 3, question: 'Are there any incentives for using code 120?', answer: 'Yes! We plan to offer quarterly prize draws, recognition features, and a Hall of Fame for students and teachers who book through centre code 120.' },
  { id: 4, question: 'Can teachers use this platform?', answer: 'Absolutely. Music Exams Help is designed to support both teachers and students. Teachers can guide their students through the booking process with confidence.' },
]

const breadcrumbPages = [
  { name: 'Demo', href: '/demo', current: false },
  { name: 'Constructors', href: '/demo/constructors', current: true },
]
</script>

<template>
  <Head
    title="Constructors Demo"
    description="Demo page for reusable constructors."
  />

  <AppLayout :breadcrumbs="breadcrumbPages">
    <template #header>
      <PageHeader
        title="Constructors Demo"
        subtitle="Visual preview of your reusable component system"
        eyebrow="System Preview"
        size="hero"
        showUnderline
      />
    </template>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
      <div class="space-y-12">

        <!-- TYPOGRAPHY -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Typography</template>
          </MyTextConstructor>

          <div class="mt-6 space-y-6">
            <MyTextConstructor variant="display" fontFamily="display" spacing="tight">
              <template #myTitle>Display text example</template>
            </MyTextConstructor>

            <MyTextConstructor variant="heading" fontFamily="display" spacing="tight">
              <template #myTitle>Heading text example</template>
            </MyTextConstructor>

            <MyTextConstructor variant="subheading" spacing="tight">
              <template #myTitle>Subheading text example</template>
            </MyTextConstructor>

            <MyTextConstructor bodyVariant="body" spacing="none">
              This is body text. It should feel comfortable to read and responsive across screen sizes.
            </MyTextConstructor>

            <MyTextConstructor subTitleVariant="muted" spacing="none">
              <template #mySubTitle>
                This is muted text for supporting copy, labels and softer explanatory text.
              </template>
            </MyTextConstructor>
          </div>
        </section>

        <!-- BUTTONS -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Buttons</template>
          </MyTextConstructor>

          <div class="mt-6 flex flex-wrap gap-4">
            <MyButtonConstructor variant="primary" size="small">
              Primary Small
            </MyButtonConstructor>

            <MyButtonConstructor variant="primary" size="medium">
              Primary Medium
            </MyButtonConstructor>

            <MyButtonConstructor variant="primary" size="large">
              Primary Large
            </MyButtonConstructor>

            <MyButtonConstructor variant="secondary" size="medium">
              Secondary
            </MyButtonConstructor>

            <MyButtonConstructor variant="outline" size="medium">
              Outline
            </MyButtonConstructor>

            <MyButtonConstructor variant="ghost" size="medium">
              Ghost
            </MyButtonConstructor>

            <MyButtonConstructor variant="light" size="medium">
              Light
            </MyButtonConstructor>

            <MyButtonConstructor variant="success" size="medium">
              Success
            </MyButtonConstructor>

            <MyButtonConstructor variant="danger" size="medium">
              Danger
            </MyButtonConstructor>
          </div>
        </section>

        <!-- ALERTS -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Alerts</template>
          </MyTextConstructor>

          <div class="mt-6 grid gap-5 lg:grid-cols-2">
            <MyAlert
              title="Info alert"
              sub-title="This is the default info alert using your main system styles."
              button-text="Learn More"
              variant="info"
            />

            <MyAlert
              title="Success alert"
              sub-title="This version is useful for positive confirmations and milestones."
              button-text="Great"
              variant="success"
            />

            <MyAlert
              title="Warning alert"
              sub-title="Useful when something needs attention but is not an error."
              button-text="Check it"
              variant="warning"
            />

            <MyAlert
              title="Neutral alert"
              sub-title="A simple neutral alert for general notices and softer callouts."
              button-text="Open"
              variant="neutral"
            />
          </div>
        </section>

        <!-- INPUTS -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Inputs</template>
          </MyTextConstructor>

          <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <MyInputConstructor
              v-model="inputValue"
              label="Your name"
              placeholder="Enter your name"
            />

            <MyInputConstructor
              v-model="emailValue"
              type="email"
              label="Email address"
              placeholder="Enter your email"
              variant="solid"
            />

            <MyInputConstructor
              model-value="Readonly example"
              label="Readonly"
              readonly
            />

            <MyInputConstructor
              model-value=""
              label="Error state"
              placeholder="Something went wrong"
              error="Please fill out this field properly."
            />

            <MyInputConstructor
              model-value="Looks good"
              label="Success state"
              success="Everything looks good here."
            />

            <MyInputConstructor
              model-value=""
              label="Disabled"
              placeholder="Disabled input"
              disabled
            />
          </div>
        </section>

        <!-- TEXTAREA -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Textarea</template>
          </MyTextConstructor>

          <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <MyTextareaConstructor
              v-model="textareaValue"
              label="Message"
              placeholder="Write your message here"
              :rows="4"
            />

            <div class="space-y-6">
              <MyTextareaConstructor
                model-value=""
                label="Textarea with error"
                placeholder="Try typing here"
                error="This message needs more detail."
              />

              <MyTextareaConstructor
                model-value="Submitted content"
                label="Textarea with success"
                success="Your message is ready."
              />
            </div>
          </div>
        </section>

        <!-- PROGRESS -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Progress</template>
          </MyTextConstructor>

          <div class="mt-6 space-y-8">
            <MyProgress
              :percentage="progressBasic"
              label="Basic progress"
              show-label
              show-percentage
              color="blue"
            />

            <MyProgress
              :percentage="progressSuccess"
              label="Success progress"
              show-label
              show-percentage
              color="green"
              gradient
            />

            <MyProgress
              :percentage="progressWarning"
              label="Warning progress"
              show-label
              show-percentage
              color="amber"
              striped
            />

            <MyProgress
              :percentage="progressDanger"
              label="Animated progress"
              show-label
              show-percentage
              color="red"
              animated
            />

            <MyProgress
              :percentage="60"
              label="Indeterminate example"
              show-label
              color="purple"
              indeterminate
            />
          </div>
        </section>

        <!-- RUNNERS -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Runner Constructor</template>
          </MyTextConstructor>

          <div class="mt-8 space-y-12">
            <div>
              <MyTextConstructor variant="subheading" spacing="tight">
                <template #myTitle>Text Variant</template>
              </MyTextConstructor>

              <div class="mt-4">
                <MyRunnerConstructor
                  variant="text"
                  :theArray="runnerTextItems"
                  :columns="2"
                  maxWidth="4xl"
                  @cardClick="handleRunnerClick"
                />
              </div>
            </div>

            <div>
              <MyTextConstructor variant="subheading" spacing="tight">
                <template #myTitle>Icon Variant</template>
              </MyTextConstructor>

              <div class="mt-4">
                <MyRunnerConstructor
                  variant="icon"
                  :theArray="runnerIconItems"
                  :columns="3"
                />
              </div>
            </div>

            <div>
              <MyTextConstructor variant="subheading" spacing="tight">
                <template #myTitle>Image Variant</template>
              </MyTextConstructor>

              <div class="mt-4">
                <MyRunnerConstructor
                  variant="image"
                  :theArray="runnerImageItems"
                  :columns="3"
                />
              </div>
            </div>
          </div>
        </section>

        <!-- TABLE -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Table Constructor</template>
          </MyTextConstructor>

          <div class="mt-6">
            <MyTableConstructor
              :data="tableData"
              :columns="tableColumns"
              title="Exam Bookings"
              subtitle="Sample student booking data for centre code 120"
              :striped="true"
              :bordered="true"
              :hoverable="true"
              :clickableRows="true"
              size="medium"
            />
          </div>
        </section>

        <!-- ACCORDION -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Accordion Constructor</template>
          </MyTextConstructor>

          <div class="mt-6">
            <MyAccordionConstructor
              :items="accordionItems"
              size="medium"
            />
          </div>
        </section>

        <!-- BANNER -->
        <section class="rounded-3xl border border-brand-border bg-brand-surface p-6 shadow-sm sm:p-8">
          <MyTextConstructor variant="eyebrow" spacing="tight">
            <template #myTitle>Banner</template>
          </MyTextConstructor>

          <div class="mt-6 space-y-6">
            <MyBanner
              text="Book your Trinity exam with centre code 120 today"
              button-text="Book Now"
              variant="dark"
              rounded
            />

            <MyBanner
              text="Need help with the booking process?"
              button-text="Get Support"
              variant="primary"
              rounded
            />

            <MyBanner
              text="Join our quarterly prize draw for students and teachers"
              button-text="Learn More"
              variant="default"
              rounded
            />
          </div>
        </section>

      </div>
    </div>
  </AppLayout>
</template>
