<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260827191844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add favoriteGifts many-to-many relation between User and Gift';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_favorite_gift (user_id INT NOT NULL, gift_id INT NOT NULL, PRIMARY KEY (user_id, gift_id))');
        $this->addSql('CREATE INDEX IDX_A4F42D62A76ED395 ON user_favorite_gift (user_id)');
        $this->addSql('CREATE INDEX IDX_A4F42D6297A95A83 ON user_favorite_gift (gift_id)');
        $this->addSql('ALTER TABLE user_favorite_gift ADD CONSTRAINT FK_A4F42D62A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorite_gift ADD CONSTRAINT FK_A4F42D6297A95A83 FOREIGN KEY (gift_id) REFERENCES gift (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_favorite_gift DROP CONSTRAINT FK_A4F42D62A76ED395');
        $this->addSql('ALTER TABLE user_favorite_gift DROP CONSTRAINT FK_A4F42D6297A95A83');
        $this->addSql('DROP TABLE user_favorite_gift');
    }
}
