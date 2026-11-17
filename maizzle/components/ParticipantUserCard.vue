<template>
  <Raw>{% if not participant.isAnonymous %}</Raw>
    <UserCard
      name="{{ participant.participantUser.pseudo }}"
      avatar="{{ absolute_url(user_avatar_image_url(participant.participantUser)) }}"
      with-profile
    />
  <Raw>{% endif %}</Raw>
</template>
