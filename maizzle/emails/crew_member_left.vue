<template>
    <Layout>
        <TwigPreheader><Raw>{{ member.pseudo }}</Raw> a quitté le groupe « <Raw>{{ crewName }}</Raw> »</TwigPreheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1><Raw>{{ member.pseudo }}</Raw> a quitté le groupe « <Raw>{{ crewName }}</Raw> »</h1>

                <p>
                    Bonjour,
                </p>

                <p>
                    <b><Raw>{{ member.pseudo }}</Raw></b> ne fait plus partie du groupe <b>« <Raw>{{ crewName }}</Raw> »</b>.
                </p>

                <Raw>{% if newOwnerName|default(false) %}</Raw>
                    <p>
                        <b><Raw>{{ newOwnerName }}</Raw></b> en est maintenant propriétaire : c'est la seule personne qui peut supprimer le groupe.
                    </p>
                <Raw>{% endif %}</Raw>

                <Raw>{% if not accountDeleted|default(false) %}</Raw>
                    <div class="my-6">
                        <UserCard
                            name="{{ member.pseudo }}"
                            avatar="{{ absolute_url(user_avatar_image_url(member)) }}"
                            with-profile
                        />
                    </div>
                <Raw>{% endif %}</Raw>

                <p>
                    Les cadeaux qu'il ou elle y avait ajoutés restent dans les sections du groupe : vous pouvez toujours les gérer, et les réservations comme les cagnottes continuent.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir les sections du groupe" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>
