<div align="center">

<img src="assets/icons/fluent-color/gift-24.svg" alt="Logo My Gift" width="96" height="96">

# My Gift

**Offrir à plusieurs, sans doublon, en gardant la surprise.**

Des sections de cadeaux à partager : on réserve un cadeau, ou on met dans la cagnotte ce qu'on peut.

![Symfony 7.4](https://img.shields.io/badge/Symfony-7.4-000000?logo=symfony&logoColor=white)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![PostgreSQL 16](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![daisyUI 5](https://img.shields.io/badge/daisyUI-5-5A0EF8?logo=daisyui&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Castor](https://img.shields.io/badge/Castor-task_runner-E8A33D)
[![Licence AGPL-3.0-or-later](https://img.shields.io/badge/Licence-AGPL--3.0--or--later-blue?logo=gnu&logoColor=white)](LICENSE)

</div>

## Ce que fait My Gift

- **Des sections de cadeaux**, une par occasion, avec un lien à partager. Chacun voit ce qui est libre, réservé ou en cagnotte.
- **Réserver ou participer.** Un cadeau se réserve en entier, ou se finance à plusieurs jusqu'à couvrir son prix. La personne fêtée ne voit jamais les montants, et aucun paiement ne passe par l'application.
- **Des groupes** pour tenir ses sections à plusieurs : un couple, des parents, une bande d'ami·es.
- **Des sections privées**, visibles des amis seulement, que l'on invite avec un lien.
- **Un préremplissage** des cadeaux à partir de l'adresse d'une page produit (titre, description, image, prix).

L'accessibilité (RGAA) est suivie à chaque évolution.

## Stack technique

| Domaine | Technologies |
| --- | --- |
| Backend | PHP 8.4, Symfony 7.4, Doctrine ORM et Migrations, Workflow (cycle de vie des cadeaux), Messenger et Scheduler |
| Base de données | PostgreSQL 16 |
| Interface | Twig, Symfony UX (Twig Components, Live Components, Turbo, Chart.js), Stimulus, Tailwind CSS 4, daisyUI 5, AssetMapper |
| Administration | EasyAdmin 5 |
| Emails | Maizzle pour les gabarits, Mailgun en production, Mailpit en développement |
| Fichiers | VichUploader, Imagick (conversion WebP), DiceBear (avatars) |
| Outillage | Castor, Docker Compose, PHP-CS-Fixer |
| Serveurs | FrankenPHP dans Docker, derrière le Caddy du serveur (production et préproduction) |

L'application n'a pas de chaîne de build Node : JavaScript et CSS passent par AssetMapper.

## Lancer l'environnement de dev

### Prérequis

- PHP 8.4 avec les extensions `imagick`, `intl`, `zip` et `pdo_pgsql`, et [Composer](https://getcomposer.org)
- [Symfony CLI](https://symfony.com/download)
- [Docker](https://docs.docker.com/get-docker/) avec Docker Compose
- [Castor](https://castor.jolicode.com/), le lanceur de tâches du projet
- Node.js, seulement pour modifier les gabarits d'emails (`maizzle/`) : les emails compilés sont versionnés

### Démarrage

```bash
git clone https://github.com/zak39/my-gift.git
cd my-gift
castor composer:install
castor symfony:start --detach && docker compose up -d --wait
castor db:m:m
castor sf:tw:build
```

Le serveur Symfony démarre en arrière-plan, et `docker compose up -d --wait` lance PostgreSQL et Mailpit, puis attend que la base soit prête. Ensuite, `castor db:m:m` crée les tables et `castor sf:tw:build` compile le CSS (ajoute `--watch` pour recompiler à chaque modification). L'adresse du site est donnée par `symfony server:status`.

Les fois suivantes, seule la ligne `castor symfony:start --detach && docker compose up -d` est à relancer.

Pour que les emails et les avatars soient traités, lance le worker Messenger dans un terminal à part :

```bash
castor sf:ca
```

### Emails et premier compte

Les emails de dev ne partent nulle part : [Mailpit](https://mailpit.axllent.org) les capture.

```bash
symfony open:local:webmail
```

Crée un compte depuis la page d'inscription, puis confirme-le avec le lien reçu dans Mailpit (le worker Messenger doit tourner). Pour accéder à `/admin` :

```bash
symfony console app:user:search <nom>
symfony console app:user:set-admin <id>
```

### Au quotidien

| Commande | Rôle |
| --- | --- |
| `castor` | Liste toutes les tâches |
| `castor stop` | Arrête le serveur et Docker (`--purge` supprime aussi les données) |
| `castor db:m:m` | Applique les migrations |
| `symfony console doctrine:migrations:diff` | Génère une migration après un changement d'entité |
| `castor db:drop` | Supprime la base (à recréer avec `symfony console doctrine:database:create` avant `castor db:m:m`) |
| `castor m:b` | Compile les gabarits d'emails (Maizzle) |
| `composer cs:check` / `composer cs:fix` | Vérifie / corrige le style (PHP-CS-Fixer, règles `@Symfony`) |

## Architecture

Le détail (cycle de vie d'un cadeau, routes rangées par point de vue, composants Twig, envoi de fichiers) est dans [CLAUDE.md](CLAUDE.md). En deux mots :

- le statut d'un cadeau suit le composant Workflow de Symfony (`config/packages/workflow.yaml`), piloté par `src/Services/Lifecycle/GiftLifecycle.php` ;
- la logique partagée entre contrôleurs vit dans des services à action unique (`src/Services/`) ;
- l'interface est faite de Twig Components rangés par niveau (`Atomic/`, `Molecule/`, `Organism/`).

## Déploiement

Un déploiement est un tag, rien d'autre : la production accepte les versions finales (`1.0.0`), la préproduction aussi les release candidates (`1.1.0-rc1`). Sur le serveur : `castor deploy`, ou `castor deploy prod 1.0.0` pour un tag précis. Sans environnement, c'est celui du serveur ; un autre est refusé avant de toucher au code. Sans tag, le plus récent est déployé. Les variables d'environnement à fournir sont décrites dans [.env.server.dist](.env.server.dist).

Sur le serveur, une commande Symfony se lance avec `castor console <commande>` (par exemple `castor console app:user:search zak`), qui l'exécute dans le conteneur php. Castor reconnaît un serveur à son `.env.prod.local` ou `.env.preprod.local`, et refuse de choisir si les deux sont présents ; sans aucun des deux, il lance `symfony console` comme sur un poste de développement. Un `symfony console` lancé directement sur le serveur ne trouve pas les variables d'environnement : seuls les conteneurs les reçoivent. Castor garde pour lui ses options globales (`-h`/`--help`, `-V`, `-q`, `-v`…) : pour les passer à la commande, ajoute `--` devant (`castor console -- doctrine:migrations:migrate --help`).

Sur un serveur, seules `castor deploy`, `castor restart:messenger` et `castor console` sont prévues. Les autres tâches Castor (`db:*`, `c:*`, `start`…) sont faites pour le poste de développement : ne les lance pas sur un serveur.

## Licence

Copyright (C) 2026 zakode

My Gift est un logiciel libre : vous pouvez le redistribuer et le modifier selon les termes
de la GNU Affero General Public License publiée par la Free Software Foundation, version 3
ou (à votre choix) toute version ultérieure. Voir [LICENSE](LICENSE).

Une version modifiée mise en ligne doit, elle aussi, offrir son code source à ceux qui
l'utilisent (article 13 de la licence).

Exceptions :

- `assets/images/zakode.webp`, la photo de l'auteur, n'est pas sous licence libre : tous
  droits réservés.
- Les icônes Lucide (licence ISC) et Fluent UI (licence MIT) et la police Modak (SIL Open
  Font License) gardent leur propre licence.

## Crédits

Icônes : [Fluent UI System Icons](https://github.com/microsoft/fluentui-system-icons) (le logo) et [Lucide](https://lucide.dev). Avatars générés avec [DiceBear](https://www.dicebear.com).
