<template>
    <Layout>
        <Preheader>Votre demande de cadeau a été réservée ! 🎁</Preheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Votre demande de cadeau a été réservée ! 🎁</h1>

                <p>
                    Bonjour <Raw>{{ recipientName|title }}</Raw>,
                </p>

                <Raw>{% if participant.isAnonymous %}</Raw>
                    <p>
                        Nous avons le plaisir de vous informer que votre cadeau <GiftTitle /> a été réservé par <b><Raw>{{ participant.pseudoAnonyme }}</Raw></b>.
                    </p>
                    <p>
                        Mais, qui est cette personne mystérieuse ? 🧐
                    </p>
                <Raw>{% else %}</Raw>
                    <p>
                        Nous avons le plaisir de vous informer que votre cadeau <GiftTitle /> a été réservé par <b><Raw>{{ participant.participantUser.pseudo }}</Raw></b>.
                    </p>
                <Raw>{% endif %}</Raw>

                <div class="my-6">
                    <ParticipantUserCard />
                </div>

                <div class="my-6">
                    <GiftCard />
                </div>

                <p>
                  Vous pouvez consulter les détails de votre demande en cliquant sur le bouton ci-dessous.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir le cadeau" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>