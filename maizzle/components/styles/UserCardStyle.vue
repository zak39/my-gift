<template>
    <style embed>
        .user-card {
            background-color: #ffffff;
            border-color: #d1d1d1;
        }

        .user-card-avatar { border-color: #d1d1d1; }

        /*
          The app's warning (emerald's --color-warning, #ffbe00, as GiftCardStyle's
          .gift-card-warning) as a soft alert: its border, a pale tint behind dark text, for
          the contrast (RGAA 3.2).
        */
        .profile-notice {
            background-color: #fff6dc;
            border-color: #ffbe00;
            color: #4d3800;
        }

        @media (prefers-color-scheme: dark) {
            .user-card {
                background-color: #1b1717 !important;
                border-color: #110d0d !important;
            }

            .user-card-avatar { border-color: #110d0d !important; }

            .profile-notice {
                background-color: #3a2f12 !important;
                border-color: #ffbe00 !important;
                color: #fde7a8 !important;
            }
        }
    </style>
</template>
