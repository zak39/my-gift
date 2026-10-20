<script setup>
  // The gift's title in bold, then its crew for a crew's gift: « votre cadeau <title> du
  // groupe « X » ». crewOf is worded in PHP (GiftOwnerLabel, from
  // WorkflowGiftSubscriber::sendToOwners()), as in the subjects.
</script>

<template>
  <b><Raw>{{ gift.title }}</Raw></b><Raw>{% if crewOf %} {{ crewOf }}{% endif %}</Raw>
</template>
