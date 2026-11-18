<template>
    <Layout>
        <TwigPreheader><Raw>{{ inviter.pseudo }}</Raw> vous invite à rejoindre le groupe « <Raw>{{ crewName }}</Raw> »</TwigPreheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Vous êtes invité·e dans un groupe ! 🎁</h1>

                <p>
                    Bonjour <Raw>{{ invitee.firstname|title }}</Raw>,
                </p>

                <p>
                    <b><Raw>{{ inviter.pseudo }}</Raw></b> vous invite à rejoindre le groupe <b>« <Raw>{{ crewName }}</Raw> »</b>.
                </p>

                <p>
                    Les membres d'un groupe tiennent des sections de cadeaux ensemble : chacun peut y ajouter des cadeaux, et la surprise reste entière pour tous.
                </p>

                <div class="my-6">
                    <UserCard
                        name="{{ inviter.pseudo }}"
                        avatar="{{ absolute_url(user_avatar_image_url(inviter)) }}"
                        with-profile
                    />
                </div>

                <p>
                    Vous pouvez accepter ou refuser l'invitation depuis la page « Mes groupes ».
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir l'invitation" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>
