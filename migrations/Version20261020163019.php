<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261020163019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A crew gift or section keeps no author once its user deleted their account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gift ALTER created_by_id DROP NOT NULL');
        $this->addSql('ALTER TABLE section ALTER created_by_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // An author back for what lost theirs: the crew's owner, as account deletion did.
        $this->addSql('UPDATE gift g SET created_by_id = c.owner_id FROM section s JOIN crew c ON c.id = s.crew_id WHERE g.section_id = s.id AND g.created_by_id IS NULL');
        $this->addSql('UPDATE section s SET created_by_id = c.owner_id FROM crew c WHERE c.id = s.crew_id AND s.created_by_id IS NULL');
        $this->addSql('ALTER TABLE gift ALTER created_by_id SET NOT NULL');
        $this->addSql('ALTER TABLE section ALTER created_by_id SET NOT NULL');
    }
}
