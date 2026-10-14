<script setup lang="ts">
/**
 * Maizzle's <Preheader> flattens its slot to plain text, which drops whatever
 * sits inside a <Raw> (a component node): the Twig variables are lost. This one
 * renders its slot untouched. The text length is unknown at build time (it is
 * filled in by Twig), so the filler count is fixed.
 */
withDefaults(defineProps<{ spaces?: number }>(), { spaces: 100 })
</script>

<template>
  <Teleport to="body:start">
    <div style="display: none"><slot /><template v-for="i in spaces" :key="i">&#8199;&#65279;&#847; </template>&nbsp;</div>
  </Teleport>
</template>
