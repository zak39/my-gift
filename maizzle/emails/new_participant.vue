<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Un nouveau participant a rejoint pour le cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw></Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Un nouveau participant a rejoint pour le cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> ! 🎁</h1>

                    <p>
                        Bonjour,
                    </p>

                    <Raw>{% if participant.isAnonymous %}</Raw>
                        <p>
                            <b><Raw>{{ participant.pseudoAnonyme }}</Raw></b> a rejoint en tant que participant pour le cadeau <b><Raw>{{ gift.title }}</Raw></b>.
                        </p>
                        <p>
                            Mais, qui est cette personne mystérieuse ? 🧐
                        </p>
                    <Raw>{% else %}</Raw>
                        <p>
                          <b><Raw>{{ participant.participantUser.fullName|title }}</Raw></b> a rejoint en tant que participant pour le cadeau <b><Raw>{{ gift.title }}</Raw></b>.
                        </p>
                    <Raw>{% endif %}</Raw>

                    <p>
                      Certe personne a contribué au cadeau pour <b><Raw>{{ participant.amount|format_currency('EUR') }}</Raw></b>.
                    </p>

                    <p>
                      Vous pouvez consulter les détails du cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> en cliquant sur le bouton ci-dessous.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir la progression du cadeau de {{ gift.createdBy.firstname|title }}" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>