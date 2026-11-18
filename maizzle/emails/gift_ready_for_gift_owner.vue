<template>
    <Layout>
        <Preheader>Votre cadeau est prêt ! 🎁</Preheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Votre cadeau est prêt ! 🎁</h1>

                <p>
                    Bonjour <Raw>{{ recipientName|title }}</Raw>,
                </p>

                <p>
                  Nous avons le plaisir de vous informer que votre cadeau <GiftTitle /> est prêt ! 🎁
                </p>

                <Raw>{% if crewOf %}</Raw>
                    <p>
                      Plusieurs participants ont complété sa cagnotte ❤️
                    </p>
                    <p>
                      Regardez qui y a participé en cliquant sur le bouton ci-dessous.
                    </p>
                <Raw>{% else %}</Raw>
                    <p>
                      Plusieurs participants ont contribué à votre cadeau pour vous faire plaisir ❤️
                    </p>

                    <p>
                      Regardez qui vous a offert <b><Raw>{{ gift.title }}</Raw></b> en cliquant sur le bouton ci-dessous.
                    </p>
                <Raw>{% endif %}</Raw>

                <div class="text-center my-6">
                    <AccentButton label="Voir le cadeau" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>