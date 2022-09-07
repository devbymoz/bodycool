<?php

namespace App\Form;

use App\Entity\Franchise;
use App\Entity\Permission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\UserType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;


/**
 * Formulaire pour la création d'une nouvelle franchise.
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
                'attr' => array(
                    'placeholder' => 'Entrez le nom de la franchise',
                    'class' => 'input'
                ),
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
            // Ajout du formulaire d'un nouvel utilisateur
            ->add('userOwner', UserType::class)
            
            // Selection des permissions
            ->add('globalPermissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => 'id',
                'choice_value'=> 'id',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('u')
                    ->orderBy('u.name', 'ASC');
                },
                'choice_attr' => function() {
                    return ['class' => 'state-checkbox'];
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
