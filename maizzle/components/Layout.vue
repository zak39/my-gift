<!--
  Copy of the built-in <Layout> of @maizzle/framework 6.1.3, which it overrides in every email.
  Only change: Inter is loaded from the app (assets/fonts/inter/inter.css, through AssetMapper)
  instead of Google Fonts, so opening an email does not send the reader's IP address to Google.
  When upgrading Maizzle, compare with node_modules/@maizzle/framework/dist/components/Layout.vue.
-->
<script setup>
import { computed, useAttrs, createStaticVNode } from 'vue'
import { twMerge } from 'tailwind-merge'
import { useOutlookFallback } from '@maizzle/framework'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  bodyClass: {
    type: String,
    default: ''
  },
  lang: {
    type: String,
    default: 'en'
  },
  dir: {
    type: String,
    default: 'ltr'
  },
  doubleHead: {
    type: [Boolean, String],
    default: false
  },
  ariaLabel: {
    type: String,
    default: undefined
  },
  outlookFallback: {
    type: Boolean,
    default: null,
  },
})

const outlookFallback = useOutlookFallback(props.outlookFallback)

const attrs = useAttrs()
const bodyMergedClass = computed(() => twMerge('m-0 p-0 size-full [word-break:break-word]', props.bodyClass))
const articleMergedClass = computed(() => twMerge('[font-size:max(16px,1rem)] font-inter', attrs.class))

const EmptyHead = () => createStaticVNode('<head></head>', 1)

const MsoHead = () => createStaticVNode(
  `<!--[if mso]>
    <style>
      td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
      .mso-break-all {word-break: break-all;}
    </style>
  <![endif]-->`,
  1
)

const msoBody = `<!--[if mso]>
  <xml>
    <o:OfficeDocumentSettings>
      <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    <w:WordDocument>
      <w:DontUseAdvancedTypographyReadingMail />
    </w:WordDocument>
  </xml>
<![endif]-->`

const htmlXmlns = computed(() => outlookFallback ? {
  'xmlns:v': 'urn:schemas-microsoft-com:vml',
  'xmlns:o': 'urn:schemas-microsoft-com:office:office',
} : {})
</script>

<template>
  <html :lang="lang" :dir="dir" v-bind="htmlXmlns">
  <EmptyHead v-if="props.doubleHead === true || props.doubleHead === 'true'" />
  <head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <MsoHead v-if="outlookFallback" />
    <link href="{{ absolute_url(asset('fonts/inter/inter.css')) }}" rel="stylesheet" media="screen">
    <style>
      @import "@maizzle/tailwindcss";

      img {
        @apply max-w-full align-middle;
      }
    </style>
  </head>
  <body :xml:lang="outlookFallback ? lang : null" :class="bodyMergedClass">
    <span v-if="outlookFallback" style="display: none" v-html="msoBody"></span>
    <div
      role="article"
      aria-roledescription="email"
      :aria-label="ariaLabel"
      :lang="lang"
      :dir="dir"
      style="font-size: medium;"
      data-juice-duplicates
      v-bind="{ ...attrs, class: undefined }"
      :class="articleMergedClass"
    >
      <slot />
    </div>
  </body>
  </html>
</template>
