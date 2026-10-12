<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261011073946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cascade friend requests with their books; date a participation whose user deleted their account';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT fk_f284d94f624b39d');
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT fk_f284d94e92f8f78');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT FK_F284D94F624B39D FOREIGN KEY (sender_id) REFERENCES friend_book (owner_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT FK_F284D94E92F8F78 FOREIGN KEY (recipient_id) REFERENCES friend_book (owner_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE participant ADD user_deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        // Participations left without a user before this constraint (the generated
        // User::removeParticipation() could do it): they become what they are, kept from a
        // user who is gone, or the constraint below would refuse them.
        $this->addSql('UPDATE participant SET user_deleted_at = NOW() WHERE participant_user_id IS NULL AND user_deleted_at IS NULL');
        // Doctrine maps no CHECK constraint: a participation loses its user only with the
        // date of the account's deletion (Participant::detachDeletedUser()).
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT CHK_PARTICIPANT_USER CHECK (participant_user_id IS NOT NULL OR user_deleted_at IS NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT FK_F284D94F624B39D');
        $this->addSql('ALTER TABLE friend_request DROP CONSTRAINT FK_F284D94E92F8F78');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT fk_f284d94f624b39d FOREIGN KEY (sender_id) REFERENCES friend_book (owner_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT fk_f284d94e92f8f78 FOREIGN KEY (recipient_id) REFERENCES friend_book (owner_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participant DROP CONSTRAINT CHK_PARTICIPANT_USER');
        $this->addSql('ALTER TABLE participant DROP user_deleted_at');
    }
}
