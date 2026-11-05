<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Votre cadeau est prêt ! 🎁</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Votre cadeau est prêt ! 🎁</h1>

                    <p>
                        Bonjour<Raw>{% if recipientName %} {{ recipientName|title }}{% endif %}</Raw>,
                    </p>

                    <p>
                      Nous avons le plaisir de vous informer que votre cadeau <GiftTitle /> est prêt ! 🎁
                    </p>

                    <Raw>{% if crewOf %}</Raw>
                        <p>
                          Plusieurs participants ont complété sa cagnotte ❤️
                        </p>
                        <p>
                          Regardez qui y a participé en cliquant sur le bouton ci-dessous.
                        </p>
                    <Raw>{% else %}</Raw>
                        <p>
                          Plusieurs participants ont contribué à votre cadeau pour vous faire plaisir ❤️
                        </p>

                        <p>
                          Regardez qui vous a offert <b><Raw>{{ gift.title }}</Raw></b> en cliquant sur le bouton ci-dessous.
                        </p>
                    <Raw>{% endif %}</Raw>

                    <div class="text-center my-6">
                        <AccentButton label="Voir le cadeau" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>