<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


/**
 * FORMULAIRE POUR LA CRÉATION D'UN NOUVEL UTILISATEUR
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Entrez le prénom du propriétaire',
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Entrez le nom du propriétaire',
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
            ->add('phone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => [
                    'placeholder' => 'Entrez son numéro de téléphone',
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 10,
                        'max' => 10,
                        'exactMessage' => 'Le numéro doit être composé de 10 chiffres'
                    ])
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Entrez l’adresse email du propriétaire',
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Email([
                        'message' => 'Cet email n\'est pas valide.'
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle de l\'utilisateur',
                'expanded' => false,
                'multiple' => false,
                'choices'  => [
                    'Gestionnaire' => 'ROLE_GESTIONNAIRE',
                    'Franchisé' => 'ROLE_FRANCHISE',
                    'Technicien' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN'
                ],
                'attr' => [
                    'class' => 'input'
                ]
            ]);
        //   
        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($roles) {
                    // transform the array to a string
                    return $roles[0] ?? null;
                },
                function ($roles) {
                    // transform the string back to an array
                    return [$roles];
                }
            ));
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'data_class' => User::class
        ]);
    }
}
