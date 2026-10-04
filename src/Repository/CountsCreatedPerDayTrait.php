<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Native SQL helpers for repositories of entities with a createdAt column. Table and column
 * names come from the mapping, quoted for the platform ("user" is reserved in PostgreSQL).
 *
 * @phpstan-require-extends ServiceEntityRepository
 */
trait CountsCreatedPerDayTrait
{
    /**
     * How many entities were created each day since the given date, for the admin dashboard.
     * Days without any creation are absent: the caller fills them with 0.
     *
     * @return array<string, int> keyed by day ('Y-m-d')
     */
    public function countCreatedPerDay(\DateTimeImmutable $since): array
    {
        [$table, $createdAt] = $this->quotedTableAndColumn('createdAt');

        return array_map('intval', $this->getEntityManager()->getConnection()
            ->executeQuery(
                "SELECT TO_CHAR($createdAt, 'YYYY-MM-DD') AS day, COUNT(*)
                FROM $table
                WHERE $createdAt >= :since
                GROUP BY day
                ORDER BY day",
                ['since' => $since->format('Y-m-d 00:00:00')],
            )
            ->fetchAllKeyValue()
        );
    }

    /**
     * @return array{string, string} the quoted table name and the quoted column of $field
     */
    private function quotedTableAndColumn(string $field): array
    {
        $entityManager = $this->getEntityManager();
        $quoteStrategy = $entityManager->getConfiguration()->getQuoteStrategy();
        $metadata = $this->getClassMetadata();
        $platform = $entityManager->getConnection()->getDatabasePlatform();

        return [
            $quoteStrategy->getTableName($metadata, $platform),
            $quoteStrategy->getColumnName($field, $metadata, $platform),
        ];
    }
}
