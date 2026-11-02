<!--
  The app's daisyUI colours of the same name (assets/styles/app.css: emerald, then dark under
  prefers-color-scheme), in hex for the mail clients. Change them with the theme.
-->
<template>
    <style embed>
        .gift-card {
            background-color: #ffffff;
            border-color: #d1d1d1;
        }

        .gift-card-figure { background-color: #e8e8e8; }
        .gift-card-muted { color: #707682; }

        .gift-card-pool {
            background-color: #f1f1f1;
            border-color: #d1d1d1;
        }

        .gift-card-track { background-color: #cbcdd0; }

        .gift-card-neutral { background-color: #333c4d; color: #f9fafb; }
        .gift-card-primary { background-color: #7a47d9; color: #ffffff; }
        .gift-card-secondary { background-color: #377cfb; color: #ffffff; }
        .gift-card-info { background-color: #00b5ff; color: #000000; }
        .gift-card-warning { background-color: #ffbe00; color: #000000; }
        .gift-card-success { background-color: #007840; color: #effdf3; }

        @media (prefers-color-scheme: dark) {
            .gift-card {
                background-color: #1b1717 !important;
                border-color: #110d0d !important;
            }

            .gift-card-figure { background-color: #161212 !important; }
            .gift-card-muted { color: #969494 !important; }

            .gift-card-pool {
                background-color: #181414 !important;
                border-color: #110d0d !important;
            }

            .gift-card-track { background-color: #3c3838 !important; }

            .gift-card-neutral { background-color: #09090b !important; color: #e4e4e7 !important; }
            .gift-card-primary { background-color: #ab90f8 !important; color: #261547 !important; }
            .gift-card-secondary { background-color: #f43098 !important; color: #f9e4f0 !important; }
        }
    </style>
</template>
