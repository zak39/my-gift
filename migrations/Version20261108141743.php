<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\ByteString;

final class Version20261108141743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A friend book\'s invitation link, unique: the existing books get a random one';
    }

    public function up(Schema $schema): void
    {
        // Nullable first, filled, then required: the existing books have none yet.
        $this->addSql('ALTER TABLE friend_book ADD invitation_slug VARCHAR(50) DEFAULT NULL');

        // $schema is the database as it is: without a friend_book table yet (--dry-run on an
        // empty database), there is no book to give a link.
        if ($schema->hasTable('friend_book')) {
            // Read before the statements above run: only owner_id, which exists already. The
            // draw of InvitationSlugGenerator, copied: a migration stays as it was written.
            foreach ($this->connection->fetchFirstColumn('SELECT owner_id FROM friend_book ORDER BY owner_id') as $ownerId) {
                $slug = ByteString::fromRandom(12, 'abcdefghjkmnpqrstuvwxyz23456789')->toString();
                $this->addSql('UPDATE friend_book SET invitation_slug = :slug WHERE owner_id = :ownerId', ['slug' => $slug, 'ownerId' => $ownerId]);
            }
        }

        $this->addSql('ALTER TABLE friend_book ALTER invitation_slug SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FRIEND_BOOK_INVITATION_SLUG ON friend_book (invitation_slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_FRIEND_BOOK_INVITATION_SLUG');
        $this->addSql('ALTER TABLE friend_book DROP invitation_slug');
    }
}
