<script setup>
  // Twig can't use `>` or `<` here (escaped to `&gt;`) nor print into `class` (the braces
  // are stripped), hence truthy tests and one branch per colour.
  // An email of a deleted gift passes giftImage, its image embedded (`cid:`): the file is
  // gone by the time the worker renders it.
  defineProps({
    showPool: {
      type: Boolean,
      default: false
    },
    showContributors: {
      type: Boolean,
      default: false
    }
  })
</script>

<template>
  <table class="gift-card w-full border rounded-2xl" cellpadding="0" cellspacing="0" role="none">
    <tr>
      <td class="gift-card-figure rounded-t-2xl p-4 text-center">
        <img
          src="{{ giftImage ?? (gift.image ? absolute_url(vich_uploader_asset(gift, 'imageFile')) : 'https://img.daisyui.com/images/stock/photo-1494232410401-ad00d5433cfa.webp') }}"
          alt="{{ gift.title }}"
          height="192"
          class="mx-auto block h-48 w-auto"
        >
      </td>
    </tr>
    <tr>
      <td class="p-6">
        <Raw>{% if gift.status.value == 'reserved' %}</Raw>
          <span class="gift-card-info inline-block rounded-full px-3 py-0.5 text-base"><Raw>{{ gift.status.label }}</Raw></span>
        <Raw>{% elseif gift.status.value == 'contributing' %}</Raw>
          <span class="gift-card-accent inline-block rounded-full px-3 py-0.5 text-base"><Raw>{{ gift.status.label }}</Raw></span>
        <Raw>{% elseif gift.status.value == 'ready' %}</Raw>
          <span class="gift-card-warning inline-block rounded-full px-3 py-0.5 text-base"><Raw>{{ gift.status.label }}</Raw></span>
        <Raw>{% elseif gift.status.value == 'given' %}</Raw>
          <span class="gift-card-success inline-block rounded-full px-3 py-0.5 text-base"><Raw>{{ gift.status.label }}</Raw></span>
        <Raw>{% else %}</Raw>
          <span class="gift-card-neutral inline-block rounded-full px-3 py-0.5 text-base"><Raw>{{ gift.status.label }}</Raw></span>
        <Raw>{% endif %}</Raw>

        <h2 class="m-0 mt-3 text-base leading-snug font-semibold"><Raw>{{ gift.title|u.truncate(120, '...', false) }}</Raw></h2>
        <p class="m-0 mt-1 text-2xl font-bold"><Raw>{{ gift.price|format_currency('EUR') }}</Raw></p>

        <Raw>{% if gift.description %}</Raw>
          <p class="gift-card-muted m-0 mt-4 text-sm"><Raw>{{ gift.description|u.truncate(160, '...', false) }}</Raw></p>
        <Raw>{% endif %}</Raw>

        <template v-if="showPool">
          <Raw>{% if gift.pool.contributionPercent %}</Raw>
            <table class="gift-card-pool mt-4 w-full border rounded-2xl" cellpadding="0" cellspacing="0" role="none">
              <tr>
                <td class="p-3">
                  <table class="w-full" cellpadding="0" cellspacing="0" role="none">
                    <tr>
                      <td class="text-sm">
                        <span class="font-semibold"><Raw>{{ gift.pool.totalContributed|format_currency('EUR') }}</Raw></span>
                        <span class="gift-card-muted">réunis sur</span>
                        <span class="font-semibold"><Raw>{{ gift.price|format_currency('EUR') }}</Raw></span>
                      </td>
                      <td class="text-right text-sm font-semibold"><Raw>{{ gift.pool.contributionPercent }}</Raw>&nbsp;%</td>
                    </tr>
                  </table>

                  <table class="gift-card-track mt-2 w-full rounded-full" cellpadding="0" cellspacing="0" role="none">
                    <tr>
                      <Raw>{% if gift.pool.exceededAmount %}</Raw>
                        <td width="{{ gift.pool.contributionPercent }}%" height="8" class="gift-card-warning rounded-full text-0">&nbsp;</td>
                      <Raw>{% elseif gift.pool.complete %}</Raw>
                        <td width="{{ gift.pool.contributionPercent }}%" height="8" class="gift-card-success rounded-full text-0">&nbsp;</td>
                      <Raw>{% else %}</Raw>
                        <td width="{{ gift.pool.contributionPercent }}%" height="8" class="gift-card-accent rounded-full text-0">&nbsp;</td>
                      <Raw>{% endif %}</Raw>
                      <Raw>{% if gift.pool.contributionPercent != 100 %}</Raw>
                        <td height="8" class="text-0">&nbsp;</td>
                      <Raw>{% endif %}</Raw>
                    </tr>
                  </table>

                  <p class="gift-card-muted m-0 mt-1 text-xs">
                    <Raw>{% if gift.pool.exceededAmount %}</Raw>
                      ⚠️ Objectif dépassé de <Raw>{{ gift.pool.exceededAmount|format_currency('EUR') }}</Raw>
                    <Raw>{% elseif gift.pool.complete %}</Raw>
                      ✅ Cagnotte complète, le cadeau peut être offert
                    <Raw>{% else %}</Raw>
                      💰 Il reste <Raw>{{ gift.pool.remainingToCollect|format_currency('EUR') }}</Raw> à réunir
                    <Raw>{% endif %}</Raw>
                  </p>
                </td>
              </tr>
            </table>
          <Raw>{% endif %}</Raw>
        </template>

        <GiftParticipants v-if="showContributors" :show-amount="showPool" />
      </td>
    </tr>
  </table>
</template>
