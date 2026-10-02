<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
import UserCardStyle from '../components/styles/UserCardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Le groupe « <Raw>{{ crewName }}</Raw> » a été supprimé</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />
        <UserCardStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Le groupe « <Raw>{{ crewName }}</Raw> » a été supprimé</h1>

                    <p>
                        Bonjour <Raw>{{ member.firstname|title }}</Raw>,
                    </p>

                    <p>
                        <b><Raw>{{ creator.fullName|title }}</Raw></b>, qui avait créé le groupe <b>« <Raw>{{ crewName }}</Raw> »</b>, vient de le supprimer.
                    </p>

                    <div class="my-6">
                        <UserCard
                            name="{{ creator.fullName|title }}"
                            avatar="{{ absolute_url(vich_uploader_asset(creator, creator.avatar ? 'avatarFile' : 'avatarDefaultFile')) }}"
                            to="{{ url('app_section_user_page', { id: creator.id }) }}"
                        />
                    </div>

                    <p>
                        Pas d'inquiétude : les cadeaux que vous aviez ajoutés à ce groupe ne sont pas perdus. Ils vous attendent dans vos <b>« Cadeaux sans section »</b>, avec leurs réservations et leurs cagnottes.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir mes cadeaux" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
