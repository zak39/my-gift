<script setup>
  defineProps({
    to: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    }
  })
</script>

<template>
  <a :href="to" class="accent-button inline-block px-4 py-2.5 text-center text-sm leading-6 font-semibold no-underline">
  <!--[if mso]><i style="mso-font-width: 150%; mso-text-raise: 30px;" hidden>&emsp;</i><![endif]-->
      <span style="mso-text-raise: 16px;">{{ label }}</span>
  <!--[if mso]><i style="mso-font-width: 150%;" hidden>&emsp;&#8203;</i><![endif]-->
  </a>
</template>