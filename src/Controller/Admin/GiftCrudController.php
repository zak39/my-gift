<?php

namespace App\Controller\Admin;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use App\Repository\SectionRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GiftCrudController extends AbstractCrudController
{
    public function __construct(
        private UploaderHelper $uploaderHelper,
        private SectionRepository $sectionRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Gift::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Cadeau')
            ->setEntityLabelInPlural('Cadeaux')
            ->setDefaultSort([
                'title' => 'ASC',
            ])
        ;
    }

    public function createEntity(string $entityFqcn): Gift
    {
        return (new Gift())
            ->setStatus(GiftStatus::CREATED)
            ->setCreatedAt(new \DateTimeImmutable())
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statusChoices = [];
        foreach (GiftStatus::cases() as $status) {
            $statusChoices[$status->label()] = $status->value;
        }

        return $filters
            ->add(
                EntityFilter::new('createdBy', 'Créé par')
                    ->setFormTypeOption('value_type_options.choice_label', 'fullname')
            )
            ->add(ChoiceFilter::new('status')->setChoices($statusChoices))
            ->add(NumericFilter::new('price'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $gift = $this->getContext()->getEntity()->getInstance();

        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title'),
            SlugField::new('slug', 'Slug')
                ->onlyWhenCreating()
                ->setTargetFieldName('title'),
            ImageField::new('image')
                ->hideOnForm()
                ->formatValue(fn (?string $image, Gift $row) => $image ? $this->uploaderHelper->asset($row, 'imageFile') : null),
            TextField::new('imageFile', 'Image')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),
            ChoiceField::new('status', 'Statut')
                ->hideOnForm(),
            AssociationField::new('createdBy', 'Créé par')
                ->formatValue(fn ($createdBy) => $createdBy?->getFullname())
                ->setFormTypeOption('choice_label', 'fullname')
                ->setFormTypeOption('placeholder', 'Choisir un utilisateur'),
            UrlField::new('link', 'Lien'),
            MoneyField::new('price', 'Prix')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            AssociationField::new('section', 'Section')
                ->onlyWhenUpdating()
                // Every section the creator may file the gift in, crews' ones included
                // whoever created them, as on the front's edit form.
                ->setFormTypeOption('choices', $gift?->getCreatedBy() ? $this->sectionRepository->findManagedBy($gift->getCreatedBy()) : [])
                ->setFormTypeOption('choice_label', 'title')
                ->setFormTypeOption('placeholder', 'Choisir une section'),
            DateField::new('createdAt', 'Créé le')
                ->hideOnForm(),
            TextareaField::new('description'),
            BooleanField::new('isPublic', 'Publié'),
        ];
    }
}
