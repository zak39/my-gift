<?php

namespace App\Services\Admin\Filter;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Filters users on one of their roles, picked in a dropdown. The roles column is JSON, so
 * the generic filters (a LIKE on the column) fail on PostgreSQL: the matching ids are
 * looked up in native SQL by UserRepository::findIdsByRole().
 */
class RoleFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, TranslatableInterface|string|false|null $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle')
        ;
    }

    /**
     * ROLE_USER is left out: getRoles() adds it on the fly, it is not stored, so filtering
     * on it in the database would miss almost everyone.
     *
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $roles = array_values(array_diff($roles, ['ROLE_USER']));
        $this->dto->setFormTypeOption('value_type_options.choices', array_combine($roles, $roles));

        return $this;
    }

    /**
     * $filterDataDto->getValue() is the picked role (e.g. 'ROLE_ADMIN') and
     * $filterDataDto->getComparison() is '=' ("est") or '!=' ("n'est pas").
     */
    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $role = $filterDataDto->getValue();

        if (null === $role) {
            return;
        }

        /** @var UserRepository $userRepository */
        $userRepository = $queryBuilder->getEntityManager()->getRepository(User::class);
        $userIds = $userRepository->findIdsByRole($role);
        $isNot = ComparisonType::NEQ === $filterDataDto->getComparison();

        if ([] === $userIds) {
            if (!$isNot) {
                $queryBuilder->andWhere('1 = 0');
            }

            return;
        }

        $parameter = $filterDataDto->getParameterName();
        $operator = $isNot ? 'NOT IN' : 'IN';

        $queryBuilder
            ->andWhere(sprintf('%s.id %s (:%s)', $filterDataDto->getEntityAlias(), $operator, $parameter))
            ->setParameter($parameter, $userIds)
        ;
    }
}
