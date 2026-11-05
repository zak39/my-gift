<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Services\Admin\Filter\RoleFilter;
use App\Services\Lifecycle\UserLifecycle;
use App\Services\Url\AvatarUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
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

class UserCrudController extends AbstractCrudController
{
    /**
     * @param array<string, list<string>> $roleHierarchy security.yaml's role_hierarchy
     */
    public function __construct(
        private AvatarUrlGenerator $avatarUrlGenerator,
        #[Autowire(param: 'security.role_hierarchy.roles')]
        private array $roleHierarchy,
        private UserLifecycle $userLifecycle,
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
        $user = new User();
        $user->setCreatedAt(new \DateTimeImmutable());
        // The admin hands over the credentials: no confirmation email is sent, which
        // UserChecker would otherwise require before the first login.
        $user->setIsVerified(true);

        return $user;
    }

    public function configureActions(Actions $actions): Actions
    {
        // An admin deletes their own account from their profile, with their password.
        $notMe = fn (User $user): bool => $user !== $this->getUser();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            // One account at a time: a deletion hands crews over, sends emails, removes
            // files, and a batch failing halfway would leave part of it applied.
            ->remove(Crud::PAGE_INDEX, Action::BATCH_DELETE)
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->displayIf($notMe))
            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn (Action $action) => $action->displayIf($notMe))
        ;
    }

    /**
     * A user is deleted as from their profile, their crews handed over and their
     * participations released.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance === $this->getUser()) {
            throw $this->createAccessDeniedException('Supprimez votre propre compte depuis votre profil.');
        }

        $this->userLifecycle->delete($entityInstance);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('pseudo')
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
            ->formatValue(fn (?string $avatar, User $user) => $this->avatarUrlGenerator->imageUrl($user))
        ;

        yield TextField::new('avatarFile', 'Avatar File')
            ->setFormType(VichImageType::class)
            ->onlyWhenUpdating()
        ;

        yield TextField::new('pseudo');

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
