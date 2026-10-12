<script setup>
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Un cadeau auquel vous participiez n'existe plus</Preheader>

        <BodyStyle />
        <CardStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Le cadeau « <Raw>{{ giftTitle }}</Raw> » n'existe plus</h1>

                    <p>
                        Bonjour,
                    </p>

                    <p>
                        <b><Raw>{{ ownerName|title }}</Raw></b> a supprimé son compte My Gift. Son cadeau <b>« <Raw>{{ giftTitle }}</Raw> »</b>, auquel vous participiez, a disparu avec lui.
                    </p>

                    <Raw>{% if pooled %}</Raw>
                        <p>
                            Si de l'argent a déjà été réuni pour la cagnotte, voyez directement avec les autres participants ce que vous en faites : My Gift ne gère aucun paiement.
                        </p>
                    <Raw>{% else %}</Raw>
                        <p>
                            Si vous l'aviez déjà acheté, rien ne vous empêche de l'offrir quand même : simplement, il n'apparaît plus sur My Gift.
                        </p>
                    <Raw>{% endif %}</Raw>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
