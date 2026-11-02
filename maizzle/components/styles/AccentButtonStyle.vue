<!--
  The app's --color-primary (assets/styles/app.css), in hex for the mail clients: emerald's
  for light, dark's under prefers-color-scheme. Change them together.
-->
<template>
    <style embed>
        .accent-button {
            background-color: #7a47d9;
            color: #ffffff;
            border-radius: 0.5rem;
        }

        @media (prefers-color-scheme: dark) {
            .accent-button {
                background-color: #ab90f8 !important;
                color: #261547 !important;
                border-radius: 2rem !important;
            }
        }
    </style>
</template>