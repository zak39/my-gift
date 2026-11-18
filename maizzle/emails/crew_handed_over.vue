<template>
    <Layout>
        <Preheader>Vous reprenez un groupe sur My Gift</Preheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Vous êtes maintenant propriétaire du groupe « <Raw>{{ crewName }}</Raw> »</h1>

                <p>
                    Bonjour <Raw>{{ newOwner.firstname|title }}</Raw>,
                </p>

                <Raw>{% if accountDeleted %}</Raw>
                    <p>
                        <b><Raw>{{ previousOwnerName }}</Raw></b>, propriétaire du groupe <b>« <Raw>{{ crewName }}</Raw> »</b>, a supprimé son compte My Gift. Comme vous en êtes le membre le plus ancien, c'est vous qui reprenez le groupe.
                    </p>

                    <p>
                        Les cadeaux que <Raw>{{ previousOwnerName }}</Raw> y avait ajoutés restent dans le groupe, à votre nom, avec leurs réservations et leurs cagnottes.
                    </p>
                <Raw>{% else %}</Raw>
                    <p>
                        <b><Raw>{{ previousOwnerName }}</Raw></b> vous a confié le groupe <b>« <Raw>{{ crewName }}</Raw> »</b> : vous en êtes maintenant propriétaire. Il ou elle en reste membre.
                    </p>
                <Raw>{% endif %}</Raw>

                <p>
                    Rien ne change pour les autres membres : tout le monde gère toujours les sections et invite ses ami·es. Seule différence pour vous : vous êtes la seule personne qui peut supprimer le groupe, et pour le quitter, vous devrez d'abord le confier à un autre membre.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir le groupe" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>
