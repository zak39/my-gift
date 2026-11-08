<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Services\User\PseudoPicker;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104215548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A user\'s pseudo, unique: the existing users get their first name and a number';
    }

    public function up(Schema $schema): void
    {
        // Nullable first, filled, then required: the existing users have none yet.
        $this->addSql('ALTER TABLE "user" ADD pseudo VARCHAR(30) DEFAULT NULL');

        // $schema is the database as it is: without a "user" table yet (--dry-run on an empty
        // database), there is nobody to give a pseudo.
        if ($schema->hasTable('user')) {
            // Picked here, read before the statements above run: only id and firstname, which
            // exist already. PseudoPicker, the app's own rule for a pseudo, says in its docblock
            // that this migration uses it; it needs no database, the taken ones are kept along.
            $picker = new PseudoPicker();
            $taken = [];

            foreach ($this->connection->fetchAllAssociative('SELECT id, firstname FROM "user" ORDER BY id') as $user) {
                $pseudo = $picker->pick($picker->baseFrom((string) $user['firstname']), $taken);
                $taken[] = strtolower($pseudo);
                $this->addSql('UPDATE "user" SET pseudo = :pseudo WHERE id = :id', ['pseudo' => $pseudo, 'id' => $user['id']]);
            }
        }

        $this->addSql('ALTER TABLE "user" ALTER pseudo SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO ON "user" (pseudo)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_PSEUDO');
        $this->addSql('ALTER TABLE "user" DROP pseudo');
    }
}
