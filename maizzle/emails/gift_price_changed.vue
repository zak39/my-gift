<template>
    <Layout>
        <TwigPreheader>Le prix du cadeau <Raw>{{ ownerOf }}</Raw> a changé</TwigPreheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Le prix du cadeau <Raw>{{ ownerOf }}</Raw> a changé 💸</h1>

                <p>
                    Bonjour,
                </p>

                <p>
                  Le prix du cadeau <b><Raw>{{ gift.title }}</Raw></b> est passé de <b><Raw>{{ previousPrice|format_currency('EUR') }}</Raw></b> à <b><Raw>{{ gift.price|format_currency('EUR') }}</Raw></b>.
                </p>
                <p>
                  La cagnotte a réuni <b><Raw>{{ gift.pool.totalContributed|format_currency('EUR') }}</Raw></b> jusqu'ici. Pensez à revoir votre participation avec les autres contributeurs.
                </p>

                <p>
                  Vous pouvez consulter les détails du cadeau en cliquant sur le bouton ci-dessous.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Voir le cadeau {{ ownerOf }}" to="{{ url }}" />
                </div>
            </div>
        </Container>
    </Layout>
</template>
