<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Vous participez au cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> ! 🎁</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Vous participez au cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> ! 🎁</h1>

                    <p>
                        Bonjour <Raw>{{ participant.participantUser.firstname|title }}</Raw>,
                    </p>

                    <p>
                        Vous avez choisi de participer au cadeau <b><Raw>{{ gift.title }}</Raw></b> pour <b><Raw>{{ gift.createdBy.firstname|title }}</Raw></b>.
                    </p>

                    <p>
                      Vous pouvez consulter les détails du cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> en cliquant sur le bouton ci-dessous.
                    </p>

                    <p>
                      Vous avez contribué au cadeau pour <b><Raw>{{ participant.amount|format_currency('EUR') }}</Raw></b>.
                    </p>

                    <div class="text-center my-6">
                      <AccentButton label="Voir le cadeau de {{ gift.createdBy.firstname|title }}" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>