<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Services\Admin\Filter\RoleFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UserCrudController extends AbstractCrudController
{
    /**
     * @param array<string, list<string>> $roleHierarchy security.yaml's role_hierarchy
     */
    public function __construct(
        private UploaderHelper $uploaderHelper,
        #[Autowire(param: 'security.role_hierarchy.roles')]
        private array $roleHierarchy,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort([
                'firstname' => 'ASC',
            ])
        ;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createEntity(string $entityFqcn): User
    {
        return (new User())->setCreatedAt(new \DateTimeImmutable());
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('isVerified')
            ->add('createdAt')
            ->add(RoleFilter::new('roles', 'Rôle')->setRoles(array_keys($this->roleHierarchy)))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Information Personnelle')
            ->onlyOnDetail()
        ;

        yield IdField::new('id')
            ->hideOnForm()
        ;

        yield ImageField::new('avatar', 'Avatar')
            ->hideOnForm()
            ->formatValue(fn (?string $avatar, User $user) => $this->uploaderHelper->asset($user, $user->getAvatarField()))
        ;

        yield TextField::new('avatarFile', 'Avatar File')
            ->setFormType(VichImageType::class)
            ->onlyWhenUpdating()
        ;

        yield TextField::new('firstname');

        yield TextField::new('lastname');

        yield EmailField::new('email');

        yield DateTimeField::new('createdAt')
            ->hideOnForm()
        ;

        yield TextField::new('plainPassword', 'Mot de passe')
            ->onlyOnForms()
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Répéter le mot de passe'],
            ])
            ->setRequired(Crud::PAGE_NEW === $pageName)
        ;

        yield BooleanField::new('isVerified', 'Verifié')
            ->hideOnForm()
        ;

        // The roles of security.yaml's role_hierarchy, labelled 'Admin', 'User'…
        $roleChoices = [];
        foreach (array_keys($this->roleHierarchy) as $role) {
            $roleChoices[ucfirst(strtolower(substr($role, \strlen('ROLE_'))))] = $role;
        }

        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices($roleChoices)
            ->allowMultipleChoices()
            ->renderExpanded()
        ;

        yield FormField::addTab('Cadeaux')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('gifts', 'Cadeaux')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/user_gifts.html.twig')
        ;

        yield FormField::addTab('Sections')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('sections', 'Sections')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/user_sections.html.twig')
        ;
    }
}
