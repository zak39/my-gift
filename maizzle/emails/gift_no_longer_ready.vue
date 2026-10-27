<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Votre cadeau n'est plus prêt à être offert</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Votre cadeau n'est plus prêt</h1>

                    <p>
                        Bonjour<Raw>{% if recipientName %} {{ recipientName }}{% endif %}</Raw>,
                    </p>

                    <p>
                        Un participant s'est retiré de la cagnotte de votre cadeau <GiftTitle /> : il n'est plus prêt à être offert.
                    </p>

                    <Raw>{% if backOnOffer %}</Raw>
                        <p>
                            Il n'y a plus personne dans la cagnotte : votre cadeau est de nouveau disponible pour vos proches.
                        </p>
                    <Raw>{% else %}</Raw>
                        <p>
                            La cagnotte est rouverte : les autres participants pourront la compléter et la déclarer prête à nouveau.
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
