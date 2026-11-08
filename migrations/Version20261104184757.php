<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104184757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A user\'s last name is optional, only the first name is required';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER lastname DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Back to NOT NULL, the users who gave no last name get an empty one.
        $this->addSql('UPDATE "user" SET lastname = \'\' WHERE lastname IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER lastname SET NOT NULL');
    }
}
