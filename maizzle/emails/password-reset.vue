<template>
    <Layout>
        <Preheader>Choisissez un nouveau mot de passe pour votre compte My Gift</Preheader>

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Réinitialisez votre mot de passe 🔑</h1>

                <p>
                    Bonjour <Raw>{{ fullName }}</Raw>,
                </p>

                <p>
                    Nous avons reçu une demande de réinitialisation du mot de passe de votre compte My Gift.
                </p>

                <p>
                    Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Choisir un nouveau mot de passe" to="{{ resetUrl }}" />
                </div>

                <p>
                    Ce lien expire dans <b><Raw>{{ expiresAtMessageKey|trans(expiresAtMessageData, 'ResetPasswordBundle') }}</Raw></b> et ne peut être utilisé qu'une seule fois.
                </p>

                <p>
                    Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail : votre mot de passe actuel reste inchangé.
                </p>

                <p class="text-xs break-all">
                    Le bouton ne fonctionne pas ? Copiez ce lien dans votre navigateur :
                    <a href="{{ resetUrl }}" class="accent-link"><Raw>{{ resetUrl }}</Raw></a>
                </p>

                <p>
                    À bientôt sur My Gift !
                </p>
            </div>
        </Container>
    </Layout>
</template>
