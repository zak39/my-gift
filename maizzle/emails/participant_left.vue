<template>
    <Layout>
        <TwigPreheader>Un participant a quitté le cadeau <Raw>{{ ownerOf }}</Raw></TwigPreheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <Raw>
              {% set participantName = participant.displayName %}
            </Raw>
            <div class="email-card border rounded-2xl p-6">
                <h1>Le participant <Raw>{{ participantName }}</Raw> a quitté le cadeau <Raw>{{ ownerOf }}</Raw> ! 🎁</h1>

                <p>
                    Bonjour,
                </p>

                <Raw>{% if participant.isAnonymous %}</Raw>
                    <p>
                        <b><Raw>{{ participant.pseudoAnonyme }}</Raw></b> a quitté le cadeau <b><Raw>{{ gift.title }}</Raw></b>.
                    </p>
                    <p>
                        Mais, qui était cette personne mystérieuse ? 🧐
                    </p>
                <Raw>{% else %}</Raw>
                    <p>
                      <b><Raw>{{ participant.displayName }}</Raw></b> a quitté le cadeau <b><Raw>{{ gift.title }}</Raw></b>.
                    </p>
                <Raw>{% endif %}</Raw>

                <p>
                  Vous pouvez consulter les détails du cadeau <Raw>{{ ownerOf }}</Raw> en cliquant sur le bouton ci-dessous.
                </p>

                <div class="text-center my-6">
                  <AccentButton label="Voir la progression du cadeau {{ ownerOf }}" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>