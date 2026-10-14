<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <TwigPreheader>Un participant a quitté le cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw></TwigPreheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <Raw>
                  {% set participantName = participant.isAnonymous ? participant.pseudoAnonyme : participant.participantUser.fullName %}
                </Raw>
                <div class="email-card border rounded-lg p-6">
                    <h1>Le participant <Raw>{{ participantName }}</Raw> a quitté le cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> ! 🎁</h1>

                    <p>
                        Bonjour,
                    </p>

                    <Raw>{% if participant.isAnonymous %}</Raw>
                        <p>
                            <b><Raw>{{ participant.pseudoAnonyme }}</Raw></b> a quitté le cadeau <b><Raw>{{ gift.title }}</Raw></b>.
                        </p>
                        <p>
                            Mais, qui était cette personne mystérieuse ? 🧐
                        </p>
                    <Raw>{% else %}</Raw>
                        <p>
                          <b><Raw>{{ participant.participantUser.fullName|title }}</Raw></b> a quitté le cadeau <b><Raw>{{ gift.title }}</Raw></b>.
                        </p>
                    <Raw>{% endif %}</Raw>

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