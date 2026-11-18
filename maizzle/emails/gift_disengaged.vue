<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <TwigPreheader>Mince ! <Raw>{{ participantName }}</Raw> a retiré sa réservation ! 😢</TwigPreheader>

        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Mince ! <Raw>{{ participantName }}</Raw> a retiré sa réservation ! 😢</h1>

                <p>
                    Bonjour <Raw>{{ recipientName|title }}</Raw>,
                </p>

                <p>
                    Malheureusement, votre cadeau <GiftTitle /> n'est plus réservé par <b><Raw>{{ participantName }}</Raw></b>.
                </p>
                <Raw>{% if participant.isAnonymous %}</Raw>
                    <p>
                        Mais, qui était cette personne mystérieuse ? 🤔
                    </p>
                <Raw>{% endif %}</Raw>

                <p>
                  Vous pouvez consulter les détails de votre demande en cliquant sur le bouton ci-dessous.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir le cadeau" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>