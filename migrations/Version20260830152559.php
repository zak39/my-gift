<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830152559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update gift lifecyle and participant relationship';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gift ADD given_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE participant ADD participant_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE participant DROP given_date');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B113D631C9D FOREIGN KEY (participant_user_id) REFERENCES "user" (id)');
        $this->addSql('CREATE INDEX IDX_D79F6B113D631C9D ON participant (participant_user_id)');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d6499d1c3019');
        $this->addSql('DROP INDEX idx_8d93d6499d1c3019');
        $this->addSql('ALTER TABLE "user" DROP participant_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gift DROP given_at');
        $this->addSql('ALTER TABLE participant DROP CONSTRAINT FK_D79F6B113D631C9D');
        $this->addSql('DROP INDEX IDX_D79F6B113D631C9D');
        $this->addSql('ALTER TABLE participant ADD given_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE participant DROP participant_user_id');
        $this->addSql('ALTER TABLE "user" ADD participant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d6499d1c3019 FOREIGN KEY (participant_id) REFERENCES participant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8d93d6499d1c3019 ON "user" (participant_id)');
    }
}
