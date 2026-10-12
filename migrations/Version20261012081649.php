<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012081649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Date the last activity of a user, the scheduled deletion of an inactive account and its reminder';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Existing accounts start active today: nobody is warned on the purge's first run.
        $this->addSql('ALTER TABLE "user" ADD last_activity_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP(0) NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER last_activity_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ADD scheduled_deletion_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD inactivity_reminded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP last_activity_at');
        $this->addSql('ALTER TABLE "user" DROP scheduled_deletion_at');
        $this->addSql('ALTER TABLE "user" DROP inactivity_reminded_at');
    }
}
