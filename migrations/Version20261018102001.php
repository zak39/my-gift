<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018102001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A crew\'s owner, transferable, apart from its creator kept as history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crew DROP CONSTRAINT fk_894940b2b03a8386');
        // The role created_by_id held until now: account deletion handed it to a successor.
        $this->addSql('ALTER TABLE crew ADD owner_id INT DEFAULT NULL');
        $this->addSql('UPDATE crew SET owner_id = created_by_id');
        $this->addSql('ALTER TABLE crew ALTER owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE crew ALTER created_by_id DROP NOT NULL');
        $this->addSql('ALTER TABLE crew ADD CONSTRAINT FK_894940B2B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE crew ADD CONSTRAINT FK_894940B27E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_894940B27E3C61F9 ON crew (owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crew DROP CONSTRAINT FK_894940B2B03A8386');
        $this->addSql('ALTER TABLE crew DROP CONSTRAINT FK_894940B27E3C61F9');
        $this->addSql('DROP INDEX IDX_894940B27E3C61F9');
        // created_by_id holds the role again: the owner, a deleted creator included.
        $this->addSql('UPDATE crew SET created_by_id = owner_id');
        $this->addSql('ALTER TABLE crew DROP owner_id');
        $this->addSql('ALTER TABLE crew ALTER created_by_id SET NOT NULL');
        $this->addSql('ALTER TABLE crew ADD CONSTRAINT fk_894940b2b03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
