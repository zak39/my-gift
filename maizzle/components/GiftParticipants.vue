<script setup>
  defineProps({
    showAmount: {
      type: Boolean,
      default: false
    }
  })
</script>

<template>
  <div>
    <p class="gift-card-muted m-0 mt-4 text-sm">Participants à la cagnotte (<Raw>{{ gift.participants|length }}</Raw>)</p>
  
    <table class="mt-2 w-full" cellpadding="0" cellspacing="0" role="none">
      <Raw>{% for contributor in gift.participants %}</Raw>
        <tr>
          <td class="w-8 py-1 align-middle">
            <Raw>{% if contributor.isAnonymous or contributor.isFromDeletedUser %}</Raw>
              <table class="gift-card-secondary rounded-full" cellpadding="0" cellspacing="0" role="none">
                <tr>
                  <td width="32" height="32" class="text-center text-xs font-semibold"><Raw>{{ contributor.isAnonymous ? contributor.pseudoAnonyme|u.truncate(2)|capitalize : '?' }}</Raw></td>
                </tr>
              </table>
            <Raw>{% else %}</Raw>
              <img
                src="{{ absolute_url(user_avatar_image_url(contributor.participantUser)) }}"
                alt="Photo de profil {{ contributor.participantUser.pseudo }}"
                width="32"
                height="32"
                class="block rounded-full object-cover"
              >
            <Raw>{% endif %}</Raw>
          </td>
          <td class="py-1 pl-3 align-middle text-sm">
            <span class="font-semibold"><Raw>{{ contributor.isAnonymous or contributor.isFromDeletedUser ? contributor.displayName : contributor.participantUser.pseudo }}</Raw></span>
          </td>
          <td v-if="showAmount" class="py-1 text-right align-middle text-sm font-semibold"><Raw>{{ contributor.amount|format_currency('EUR') }}</Raw></td>
        </tr>
      <Raw>{% endfor %}</Raw>
    </table>
  </div>
</template>
