<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <TwigPreheader>Vous participez au cadeau <Raw>{{ ownerOf }}</Raw> ! 🎁</TwigPreheader>

        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Vous participez au cadeau <Raw>{{ ownerOf }}</Raw> ! 🎁</h1>

                <p>
                    Bonjour <Raw>{{ participant.participantUser.firstname|title }}</Raw>,
                </p>

                <p>
                    Vous avez choisi de participer au cadeau <b><Raw>{{ gift.title }}</Raw></b> pour <b><Raw>{{ ownerName }}</Raw></b>.
                </p>

                <p>
                  Vous pouvez consulter les détails du cadeau <Raw>{{ ownerOf }}</Raw> en cliquant sur le bouton ci-dessous.
                </p>

                <p>
                  Vous avez contribué au cadeau pour <b><Raw>{{ participant.amount|format_currency('EUR') }}</Raw></b>.
                </p>

                <div class="text-center my-6">
                  <AccentButton label="Voir le cadeau {{ ownerOf }}" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>