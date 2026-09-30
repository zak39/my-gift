<script setup>
  defineProps({
    name: {
      type: String,
      required: true
    },
    avatar: {
      type: String,
      required: true
    },
    to: {
      type: String,
      required: true
    }
  })
</script>

<template>
  <table class="user-card w-full border rounded-2xl" cellpadding="0" cellspacing="0" role="none">
    <tr>
      <td class="p-5">
        <table cellpadding="0" cellspacing="0" role="none">
          <tr>
            <td class="align-middle">
              <img :src="avatar" :alt="`Photo de profil ${name}`" width="56" height="56" class="user-card-avatar block rounded-full border-2 object-cover p-0.5">
            </td>
            <td class="pl-4 align-middle">
              <h2 class="m-0 text-lg font-semibold">{{ name }}</h2>
            </td>
          </tr>
        </table>

        <p class="m-0 mt-4 text-right text-sm font-semibold">
          <a :href="to" class="accent-link no-underline">&rarr; Voir sa liste</a>
        </p>
      </td>
    </tr>
  </table>
</template>
