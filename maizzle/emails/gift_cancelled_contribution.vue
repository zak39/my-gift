<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <TwigPreheader>Vous êtiez le dernier à participer au cadeau <Raw>{{ ownerOf }}</Raw></TwigPreheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <Raw>
                  {% set participantName = participant.participantUser.firstname %}
                </Raw>
                <div class="email-card border rounded-lg p-6">
                    <h1>Vous étiez le dernier à participer au cadeau <Raw>{{ ownerOf }}</Raw> ! 🎁</h1>

                    <p>
                        Bonjour <Raw>{{ participantName|title }}</Raw>,
                    </p>

                    <p>
                        Zut, vous étiez le dernier à participer au cadeau <b><Raw>{{ gift.title }}</Raw></b> pour <b><Raw>{{ ownerName }}</Raw></b>.
                    </p>

                    <p>
                      Maintenant, le cadeau peut-être réservé ou contribué par un autre utilisateur.
                    </p>

                    <div class="text-center my-6">
                      <AccentButton label="Voir le cadeau {{ ownerOf }}" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>