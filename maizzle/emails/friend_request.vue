<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import BodyStyle from '../components/styles/BodyStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import UserCardStyle from '../components/styles/UserCardStyle.vue'
</script>

<template>
    <Layout>
        <TwigPreheader><Raw>{{ sender.pseudo }}</Raw> souhaite vous ajouter à ses ami·es</TwigPreheader>

        <BodyStyle />
        <CardStyle />
        <AccentButtonStyle />
        <UserCardStyle />

        <Body class="email-bg">
            <Container class="max-w-xl p-0 py-10 sm:p-6">
                <div class="email-card border rounded-lg p-6">
                    <h1>Nouvelle demande d'ami·e ! 🎁</h1>

                    <p>
                        Bonjour <Raw>{{ recipient.firstname|title }}</Raw>,
                    </p>

                    <p>
                        <b><Raw>{{ sender.pseudo }}</Raw></b> souhaite vous ajouter à ses ami·es.
                    </p>

                    <p>
                        En acceptant, vous découvrirez chacun·e les envies de l'autre : <Raw>{{ sender.pseudo }}</Raw> pourra voir vos cadeaux, et vous les siens, pour faire plaisir sans jamais offrir deux fois la même chose.
                    </p>

                    <!-- No profile: not friends yet, the very thing this email asks. -->
                    <div class="my-6">
                        <UserCard
                            name="{{ sender.pseudo }}"
                            avatar="{{ absolute_url(user_avatar_image_url(sender)) }}"
                        />
                    </div>

                    <p>
                        Vous pouvez accepter ou refuser la demande depuis la page « Mes ami·es ».
                    </p>

                    <div class="text-center my-6">
                        <AccentButton label="Voir la demande" to="{{ url }}" />
                    </div>
                </div>
            </Container>
        </Body>
    </Layout>
</template>
