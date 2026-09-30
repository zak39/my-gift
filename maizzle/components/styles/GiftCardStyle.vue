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
        .gift-card-secondary { background-color: #377cfb; color: #ffffff; }
        .gift-card-info { background-color: #00b5ff; color: #000000; }
        .gift-card-accent { background-color: #f68067; color: #000000; }
        .gift-card-warning { background-color: #ffbe00; color: #000000; }
        .gift-card-success { background-color: #00a96e; color: #000000; }

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

            .gift-card-neutral { background-color: #19362d !important; color: #cdd3d1 !important; }
            .gift-card-secondary { background-color: #1eb88e !important; color: #000c07 !important; }
            .gift-card-accent { background-color: #1fb8ab !important; color: #010c0b !important; }
        }
    </style>
</template>
