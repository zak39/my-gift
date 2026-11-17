<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
import UserCardStyle from '../components/styles/UserCardStyle.vue'
</script>

<template>
    <Layout>
        <TwigPreheader><Raw>{{ member.pseudo }}</Raw> a rejoint le groupe « <Raw>{{ crewName }}</Raw> »</TwigPreheader>

        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />
        <UserCardStyle />

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1><Raw>{{ member.pseudo }}</Raw> a rejoint le groupe « <Raw>{{ crewName }}</Raw> » 🎉</h1>

                <p>
                    Bonjour,
                </p>

                <p>
                    <b><Raw>{{ member.pseudo }}</Raw></b> fait maintenant partie du groupe <b>« <Raw>{{ crewName }}</Raw> »</b>.
                </p>

                <div class="my-6">
                    <UserCard
                        name="{{ member.pseudo }}"
                        avatar="{{ absolute_url(user_avatar_image_url(member)) }}"
                        with-profile
                    />
                </div>

                <p>
                    Vous tenez désormais les sections ensemble : chacun peut y ajouter des cadeaux, et ses ami·es les verront aussi.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir les sections du groupe" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>
