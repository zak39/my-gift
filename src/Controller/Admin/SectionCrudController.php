<?php

namespace App\Controller\Admin;

use App\Entity\Section;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Section::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Section')
            ->setEntityLabelInPlural('Sections')
            ->setDefaultSort([
                'title' => 'ASC',
            ])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title'),
            // slug and createdBy are NOT NULL: on the site, SectionController fills them in
            SlugField::new('slug', 'Slug')
                ->onlyWhenCreating()
                ->setTargetFieldName('title'),
            AssociationField::new('createdBy', 'Créé par')
                ->formatValue(fn ($createdBy) => $createdBy?->getFullname())
                ->setFormTypeOption('choice_label', 'fullname')
                ->setFormTypeOption('placeholder', 'Choisir un utilisateur'),
            TextareaField::new('description'),
        ];
    }
}
