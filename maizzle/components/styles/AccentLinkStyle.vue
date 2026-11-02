<!--
  The app's --color-primary (assets/styles/app.css), in hex for the mail clients: emerald's
  for light, dark's under prefers-color-scheme. Change them together.
-->
<template>
    <style embed>
        .accent-link { color: #7a47d9; }

        @media (prefers-color-scheme: dark) {
            .accent-link { color: #ab90f8 !important; }
        }
    </style>
</template>