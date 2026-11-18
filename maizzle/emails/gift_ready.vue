<template>
    <Layout>
        <TwigPreheader>Le cadeau <Raw>{{ ownerOf }}</Raw> est prêt ! 🎉</TwigPreheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Le cadeau <Raw>{{ ownerOf }}</Raw> est prêt ! 🎉</h1>

                <p>
                    Bonjour,
                </p>

                <p>
                  Nous avons le plaisir de vous informer que le cadeau <b><Raw>{{ gift.title }}</Raw></b> <b><Raw>{{ ownerOf }}</Raw></b> est prêt !
                </p>
                <p>
                  N'oubliez pas de lui donner votre cadeau pour qu'il puisse en profiter ! 🎁
                </p>

                <p>
                  Vous pouvez consulter les détails du demande en cliquant sur le bouton ci-dessous.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir le cadeau {{ ownerOf }}" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>