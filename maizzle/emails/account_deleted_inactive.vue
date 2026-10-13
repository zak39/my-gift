<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Votre compte My Gift inactif a été supprimé</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Votre compte a été supprimé</h1>

                    <p>
                        Bonjour <Raw>{{ firstname|title }}</Raw>,
                    </p>

                    <p>
                        Comme annoncé dans nos derniers messages, votre compte My Gift, sans connexion depuis plus d'un an, a été supprimé, avec vos sections, vos cadeaux et votre photo de profil.
                    </p>

                    <ul>
                        <li>Vos réservations et vos participations en cours ont été annulées.</li>
                        <li>Les groupes que vous aviez créés ont été confiés au membre le plus ancien, ou supprimés si vous y étiez seul·e. Les cadeaux que vous aviez ajoutés à un groupe y restent.</li>
                        <li>Sur les cadeaux déjà offerts, votre participation reste, sans votre nom.</li>
                    </ul>

                    <p>
                        Vous pouvez créer un nouveau compte à tout moment avec la même adresse email.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Revenir sur My Gift" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
