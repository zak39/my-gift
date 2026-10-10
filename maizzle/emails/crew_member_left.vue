<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
import UserCardStyle from '../components/styles/UserCardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader><Raw>{{ member.firstname|title }}</Raw> a quitté le groupe « <Raw>{{ crewName }}</Raw> »</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />
        <UserCardStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1><Raw>{{ member.firstname|title }}</Raw> a quitté le groupe « <Raw>{{ crewName }}</Raw> »</h1>

                    <p>
                        Bonjour,
                    </p>

                    <p>
                        <b><Raw>{{ member.fullName|title }}</Raw></b> ne fait plus partie du groupe <b>« <Raw>{{ crewName }}</Raw> »</b>.
                    </p>

                    <div class="my-6">
                        <UserCard
                            name="{{ member.fullName|title }}"
                            avatar="{{ absolute_url(vich_uploader_asset(member, member.avatar ? 'avatarFile' : 'avatarDefaultFile')) }}"
                            to="{{ url('app_section_user_page', { id: member.id }) }}"
                        />
                    </div>

                    <p>
                        Les cadeaux qu'il ou elle y avait ajoutés restent dans les sections du groupe : vous pouvez toujours les gérer, et les réservations comme les cagnottes continuent.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir les sections du groupe" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
