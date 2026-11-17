<script setup>
import AccentButtonStyle from '../components/styles/AccentButtonStyle.vue'
import CardStyle from '../components/styles/CardStyle.vue'
import AccentLinkStyle from '../components/styles/AccentLinkStyle.vue'
</script>

<template>
    <Layout>
        <Preheader>Confirmez votre adresse e-mail pour activer votre compte My Gift</Preheader>

        <CardStyle />
        <AccentButtonStyle />
        <AccentLinkStyle />

        <Container class="max-w-xl p-0 py-10 sm:p-6">
            <div class="email-card border rounded-2xl p-6">
                <h1>Bienvenue <Raw>{{ fullName }}</Raw>!</h1>

                <p>
                    Merci de vous être inscrit sur My Gift.
                </p>

                <p>
                    Encore une étape, il faut confirmer votre adresse e-mail pour activer votre compte et commencer à utiliser toutes les fonctionnalités.
                </p>

                <p>
                    Cliquez sur le bouton ci-dessous pour activer votre compte.
                </p>

                <div class="text-center my-6">
                    <AccentButton label="Activer mon compte" to="{{ signedUrl }}" />
                </div>
                <p>
                    S'il vous plaît, confirmez votre adresse e-mail avant que le lien n'expire dans <b><Raw>{{ expiresAtMessageKey|trans(expiresAtMessageData, 'VerifyEmailBundle') }}</Raw></b> à compter de la réception du mail.
                </p>
                <p>
                    À bientôt sur My Gift!
                </p>
            </div>
        </Container>
    </Layout>
</template>