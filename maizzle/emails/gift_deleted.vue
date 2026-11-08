<script setup>
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import GiftCardStyle from '../components/styles/GiftCardStyle.vue'
import UserCardStyle from '../components/styles/UserCardStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Un cadeau auquel vous participiez n'existe plus</Preheader>

        <BodyStyle />
        <CardStyle />
        <AccentLinkStyle />
        <GiftCardStyle />
        <UserCardStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Le cadeau « <Raw>{{ gift.title }}</Raw> » n'existe plus</h1>

                    <p>
                        Bonjour,
                    </p>

                    <Raw>{% if deleter %}</Raw>
                        <p>
                            <b><Raw>{{ deleter.name }}</Raw></b> a supprimé <Raw>{% if crewOf %}le{% else %}son{% endif %}</Raw> cadeau <GiftTitle />, auquel vous participiez : il n'apparaît plus sur My Gift.
                        </p>

                        <div class="my-6">
                            <Raw>{% if deleter.profileUrl %}</Raw>
                                <UserCard name="{{ deleter.name }}" avatar="{{ absolute_url(deleter.avatar) }}" to="{{ deleter.profileUrl }}" />
                            <Raw>{% else %}</Raw>
                                <UserCard name="{{ deleter.name }}" avatar="{{ absolute_url(deleter.avatar) }}" />
                            <Raw>{% endif %}</Raw>
                        </div>
                    <Raw>{% else %}</Raw>
                        <p>
                            Le cadeau <b><Raw>{{ gift.title }}</Raw></b> <Raw>{{ ownerOf }}</Raw>, auquel vous participiez, a été supprimé par l'équipe de My Gift : il n'apparaît plus sur le site.
                        </p>
                    <Raw>{% endif %}</Raw>

                    <div class="my-6">
                        <GiftCard />
                    </div>

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
