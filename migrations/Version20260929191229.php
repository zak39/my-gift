<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Written by hand: the friendships and requests already stored must survive.
 *
 * The book shares its owner's id, so user_friend.user_id, user_blocked.user_id and
 * friend_request.sender_id / recipient_id already hold book ids. No row moves: each
 * user gets a book, then the foreign keys point at friend_book instead of "user".
 */
final class Version20260929191229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move the social graph from user to friend_book, keeping the stored friendships and requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE friend_book (owner_id INT NOT NULL, PRIMARY KEY (owner_id))');
        $this->addSql('ALTER TABLE friend_book ADD CONSTRAINT FK_519F7FD17E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');

        // Before repointing the foreign keys: every existing id they hold must have a book.
        $this->addSql('INSERT INTO friend_book (owner_id) SELECT id FROM "user"');

        $this->addSql('ALTER TABLE user_friend DROP CONSTRAINT fk_30bcb75ca76ed395');
        $this->addSql('ALTER TABLE user_friend ADD CONSTRAINT FK_30BCB75CA76ED395 FOREIGN KEY (user_id) REFERENCES friend_book (owner_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE user_blocked DROP CONSTRAINT fk_8258f58aa76ed395');
        $this->addSql('ALTER TABLE user_blocked ADD CONSTRAINT FK_8258F58AA76ED395 FOREIGN KEY (user_id) REFERENCES friend_book (owner_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT fk_f284d94e92f8f78');
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT fk_f284d94f624b39d');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT FK_F284D94E92F8F78 FOREIGN KEY (recipient_id) REFERENCES friend_book (owner_id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT FK_F284D94F624B39D FOREIGN KEY (sender_id) REFERENCES friend_book (owner_id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // The same ids are user ids: pointing back at "user" loses nothing.
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT FK_F284D94F624B39D');
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT FK_F284D94E92F8F78');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT fk_f284d94f624b39d FOREIGN KEY (sender_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT fk_f284d94e92f8f78 FOREIGN KEY (recipient_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_blocked DROP CONSTRAINT FK_8258F58AA76ED395');
        $this->addSql('ALTER TABLE user_blocked ADD CONSTRAINT fk_8258f58aa76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_friend DROP CONSTRAINT FK_30BCB75CA76ED395');
        $this->addSql('ALTER TABLE user_friend ADD CONSTRAINT fk_30bcb75ca76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_book DROP CONSTRAINT FK_519F7FD17E3C61F9');
        $this->addSql('DROP TABLE friend_book');
    }
}
