<template>
  <Raw>{% if not participant.isAnonymous %}</Raw>
    <UserCard
      name="{{ participant.participantUser.pseudo }}"
      avatar="{{ absolute_url(user_avatar_image_url(participant.participantUser)) }}"
      to="{{ url('app_section_user_page', { id: participant.participantUser.id }) }}"
    />
  <Raw>{% endif %}</Raw>
</template>
