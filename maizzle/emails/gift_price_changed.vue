<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Le prix du cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> a changé</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Le prix du cadeau de <Raw>{{ gift.createdBy.firstname|title }}</Raw> a changé 💸</h1>

                    <p>
                        Bonjour,
                    </p>

                    <p>
                      Le prix du cadeau <b><Raw>{{ gift.title }}</Raw></b> est passé de <b><Raw>{{ previousPrice|format_currency('EUR') }}</Raw></b> à <b><Raw>{{ gift.price|format_currency('EUR') }}</Raw></b>.
                    </p>
                    <p>
                      La cagnotte a réuni <b><Raw>{{ gift.totalContributed|format_currency('EUR') }}</Raw></b> jusqu'ici. Pensez à revoir votre participation avec les autres contributeurs.
                    </p>

                    <p>
                      Vous pouvez consulter les détails du cadeau en cliquant sur le bouton ci-dessous.
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir le cadeau de {{ gift.createdBy.firstname|title }}" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
