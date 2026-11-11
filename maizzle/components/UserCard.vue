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
    /*
     * Where the reader may meet this user again: their sections, or a warning that only
     * friends see them. Twig chooses when rendering, on the `profileUrl` of the email's
     * context (ProfileUrlGenerator, null for a reader who is no friend): one email per
     * reader, then. Left out where the email itself is about becoming friends.
     */
    withProfile: {
      type: Boolean,
      default: false
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

        <template v-if="withProfile">
          <Raw>{% if profileUrl %}</Raw>
            <p class="m-0 mt-4 text-right text-sm font-semibold">
              <a href="{{ profileUrl }}" class="accent-link no-underline">&rarr; Voir son profil</a>
            </p>
          <Raw>{% else %}</Raw>
            <!--
              Said rather than left out: a reader comparing with another, who has the link,
              would take its absence for a bug. The words carry the warning, not the colour
              alone (RGAA 3.1).
            -->
            <p class="profile-notice m-0 mt-4 rounded-lg border p-3 text-sm">
              &#9888;&#65039; Vous n'êtes pas encore ami·e avec <b>{{ name }}</b> : demandez-lui son lien d'invitation pour voir ses sections.
            </p>
          <Raw>{% endif %}</Raw>
        </template>
      </td>
    </tr>
  </table>
</template>
