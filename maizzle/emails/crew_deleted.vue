<template>
    <Layout>
        <TwigPreheader>Le groupe « <Raw>{{ crewName }}</Raw> » a été supprimé</TwigPreheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Le groupe « <Raw>{{ crewName }}</Raw> » a été supprimé</h1>

                <p>
                    Bonjour <Raw>{{ member.firstname|title }}</Raw>,
                </p>

                <p>
                    <b><Raw>{{ owner.pseudo }}</Raw></b>, propriétaire du groupe <b>« <Raw>{{ crewName }}</Raw> »</b>, vient de le supprimer.
                </p>

                <div class="my-6">
                    <UserCard
                        name="{{ owner.pseudo }}"
                        avatar="{{ absolute_url(user_avatar_image_url(owner)) }}"
                        with-profile
                    />
                </div>

                <p>
                    Pas d'inquiétude : les cadeaux que vous aviez ajoutés à ce groupe ne sont pas perdus. Ils vous attendent dans vos <b>« Cadeaux sans section »</b>, avec leurs réservations et leurs cagnottes.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir mes cadeaux" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>
