<?php

namespace App\Form;

use App\Entity\Crew;
use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'Geek, Sport, Maison…',
                ],
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Tout ce qui touche aux jeux vidéo et aux gadgets.',
                ],
                'label' => 'Description',
                'help' => 'Une phrase pour guider vos amis. 255 caractères maximum.',
                'required' => false,
            ])
            /*
            ->add('slug')
            ->add('createdBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            */
        ;

        if ($options['crews']) {
            $builder->add('crew', EntityType::class, [
                'class' => Crew::class,
                'choices' => $options['crews'],
                'choice_label' => 'name',
                'label' => 'Liste',
                'help' => 'Dans la liste d\'un groupe, la section est partagée : chaque membre peut y ranger des cadeaux.',
                'placeholder' => 'Ma liste',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Section::class,
            // The crews the section may be created in. The edit page passes none: a
            // section never moves from a list to another.
            'crews' => [],
        ]);
        $resolver->setAllowedTypes('crews', Crew::class.'[]');
    }
}
