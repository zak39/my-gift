<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007215031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A gift\'s price is required, as its form already says';
    }

    public function up(Schema $schema): void
    {
        // No price can be made up: a gift without one has to be fixed by hand first.
        $withoutPrice = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM gift WHERE price IS NULL');
        $this->abortIf(0 !== $withoutPrice, "$withoutPrice gift(s) have no price: set one (admin or SQL) before running this migration.");

        $this->addSql('ALTER TABLE gift ALTER price SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gift ALTER price DROP NOT NULL');
    }
}
