<template>
  <Raw>{% if not participant.isAnonymous %}</Raw>
    <UserCard
      name="{{ participant.participantUser.fullName|title }}"
      avatar="{{ absolute_url(vich_uploader_asset(participant.participantUser, participant.participantUser.avatar ? 'avatarFile' : 'avatarDefaultFile')) }}"
      to="{{ url('app_section_user_page', { id: participant.participantUser.id }) }}"
    />
  <Raw>{% endif %}</Raw>
</template>
