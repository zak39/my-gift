<?php

namespace App\Controller\Admin;

use App\Entity\Crew;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CrewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Crew::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            // A crew needs its creator (Crew::__construct(User $creator)), a member of it who
            // alone may delete it: crews are created by their members on the site, not here.
            ->disable(Action::NEW)
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Groupe')
            ->setEntityLabelInPlural('Groupes')
            ->setDefaultSort([
                'name' => 'ASC',
            ])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Général')
            ->onlyOnDetail()
        ;

        yield IdField::new('id')
            ->hideOnForm()
        ;

        yield TextField::new('name');

        yield FormField::addTab('Membres')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('memberships')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/members.html.twig')
        ;

        yield FormField::addTab('Cadeaux')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('sections')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/gifts.html.twig')
        ;
    }
}
