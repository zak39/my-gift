<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Connectez-vous pour garder votre compte My Gift</Preheader>

        <CardStyle />
        <AccentButtonStyle />

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>
                    <Raw>{% if reminder %}</Raw>Rappel : votre<Raw>{% else %}</Raw>Votre<Raw>{% endif %}</Raw> compte sera supprimé le <Raw>{{ deletionDate }}</Raw>
                </h1>

                <p>
                    Bonjour <Raw>{{ firstname|title }}</Raw>,
                </p>

                <Raw>{% if reminder %}</Raw>
                    <p>
                        Nous vous avions prévenu·e il y a quelques semaines : sans connexion de votre part, votre compte My Gift sera supprimé dans quelques jours, le <b><Raw>{{ deletionDate }}</Raw></b>.
                    </p>
                <Raw>{% else %}</Raw>
                    <p>
                        Vous ne vous êtes pas connecté·e à My Gift depuis un an. Pour ne pas garder vos données plus longtemps que nécessaire, nous supprimons les comptes inactifs : le vôtre le sera le <b><Raw>{{ deletionDate }}</Raw></b>.
                    </p>
                <Raw>{% endif %}</Raw>

                <p>
                    Pour le garder, il vous suffit de vous connecter avant cette date. Rien d'autre à faire.
                </p>

                <p>
                    Sinon, votre compte sera supprimé avec vos sections, vos cadeaux et votre photo de profil. Les groupes dont vous êtes propriétaire seront confiés à leur membre le plus ancien.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Me connecter" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>
