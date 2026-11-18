<script setup>
import CardStyle from '../components/styles/CardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Un cadeau auquel vous participiez n'existe plus</Preheader>

        <CardStyle />

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Le cadeau « <Raw>{{ giftTitle }}</Raw> » n'existe plus</h1>

                <p>
                    Bonjour,
                </p>

                <p>
                    Le cadeau <b>« <Raw>{{ giftTitle }}</Raw> »</b>, auquel vous participiez, faisait partie du groupe <b>« <Raw>{{ crewName }}</Raw> »</b>. La personne qui l'avait ajouté a supprimé son compte depuis, et
                    <Raw>{% if crewDeleted %}</Raw>le groupe vient d'être supprimé<Raw>{% else %}</Raw>sa section vient d'être supprimée<Raw>{% endif %}</Raw> : il n'avait plus personne à qui revenir, il a donc disparu avec.
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
    </Layout>
</template>
