<?php

namespace App\Form\Franchise;

use App\Entity\Franchise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\User\UserType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\Permission;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


/**
 * FORMULAIRE POUR LA CRÉATION D'UNE NOUVELLE FRANCHISE
 * Inclus le formulaire d'un nouvel utilisateur et les permissions globales
 * 
 */
class AddFranchiseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {     
        $builder
             ->add('name', TextType::class, [
                'label' => 'Nom de la franchise',
                'attr' => [
                    'placeholder' => 'Entrez le nom de la franchise',
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
            ])

            // Ajout du formulaire pour créer un un nouvel utilisateur
            ->add('userOwner', UserType::class)

            // Ajout du formulaire pour ajouter les permissions globales
            ->add('globalPermissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => 'id',
                'choice_value'=> 'id',
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('u')
                    ->orderBy('u.name', 'DESC');
                },
                'choice_attr' => function() {
                    return [
                        'class' => 'state-checkbox'
                    ];
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'data_class' => Franchise::class
        ]);
    }
}
