<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Vous reprenez un groupe sur My Gift</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Vous êtes maintenant créateur·rice du groupe « <Raw>{{ crewName }}</Raw> »</h1>

                    <p>
                        Bonjour <Raw>{{ newCreator.firstname|title }}</Raw>,
                    </p>

                    <p>
                        <b><Raw>{{ previousCreatorName|title }}</Raw></b>, qui avait créé le groupe <b>« <Raw>{{ crewName }}</Raw> »</b>, a supprimé son compte My Gift. Comme vous en êtes le membre le plus ancien, c'est vous qui reprenez le groupe.
                    </p>

                    <p>
                        Rien ne change pour les autres membres : tout le monde gère toujours les sections et invite ses ami·es. Seule différence pour vous : vous ne pouvez plus quitter le groupe, mais vous êtes la seule personne qui peut le supprimer.
                    </p>

                    <p>
                        Les cadeaux que <Raw>{{ previousCreatorName|title }}</Raw> y avait ajoutés restent dans le groupe, à votre nom, avec leurs réservations et leurs cagnottes.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir le groupe" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
