<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Le cadeau de <Raw>{{ gift.createdBy.firstname }}</Raw> est prêt ! 🎉</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Le cadeau de <Raw>{{ gift.createdBy.firstname }}</Raw> est prêt ! 🎉</h1>

                    <p>
                        Bonjour,
                    </p>

                    <p>
                      Nous avons le plaisir de vous informer que le cadeau <b><Raw>{{ gift.title }}</Raw></b> de <b><Raw>{{ gift.createdBy.firstname }}</Raw></b> est prêt !
                    </p>
                    <p>
                      N'oubliez pas de lui donner votre cadeau pour qu'il puisse en profiter ! 🎁
                    </p>

                    <p>
                      Vous pouvez consulter les détails du demande en cliquant sur le bouton ci-dessous.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir le cadeau de {{ gift.createdBy.firstname }}" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>