<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
import UserCardStyle from '../components/styles/UserCardStyle.vue'
</script>

<template>
    <Layout>
        <TwigPreheader><Raw>{{ member.firstname|title }}</Raw> a rejoint le groupe « <Raw>{{ crewName }}</Raw> »</TwigPreheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />
        <UserCardStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1><Raw>{{ member.firstname|title }}</Raw> a rejoint le groupe « <Raw>{{ crewName }}</Raw> » 🎉</h1>

                    <p>
                        Bonjour,
                    </p>

                    <p>
                        <b><Raw>{{ member.fullName|title }}</Raw></b> fait maintenant partie du groupe <b>« <Raw>{{ crewName }}</Raw> »</b>.
                    </p>

                    <div class="my-6">
                        <UserCard
                            name="{{ member.fullName|title }}"
                            avatar="{{ absolute_url(vich_uploader_asset(member, member.avatar ? 'avatarFile' : 'avatarDefaultFile')) }}"
                            to="{{ url('app_section_user_page', { id: member.id }) }}"
                        />
                    </div>

                    <p>
                        Vous tenez désormais les sections ensemble : chacun peut y ajouter des cadeaux, et ses ami·es les verront aussi.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir les sections du groupe" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
