<?php

namespace App\Form\Structure;

use App\Entity\Franchise;
use App\Entity\Structure;
use App\Form\User\UserType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddStructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la structure',
                'attr' => [
                    'placeholder' => 'Entrez le nom de la structure',
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
                'label' => 'Numéro de téléphone de la structure',
                'attr' => [
                    'placeholder' => 'Entrez le numéro de téléphone',
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
            ->add('contractNumber', TextType::class, [
                'label' => 'Numéro de contrat',
                'attr' => [
                    'placeholder' => 'Entrez le numéro de contrat',
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 5,
                        'max' => 5,
                        'exactMessage' => 'Le numéro de contrat doit avoir 5 caractères'
                    ])
                ],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse de la structure',
                'attr' => [
                    'placeholder' => 'Entrez l’adresse de la structure',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'exactMessage' => 'L\'adresse doit avoir {min} caractères minimum'
                    ])
                ],
            ])

            // Ajout du formulaire pour créer un un nouvel utilisateur
            ->add('userAdmin', UserType::class)

            // Selection d'une franchise.
            ->add('franchise', EntityType::class, [
                'label' => 'Appartient à la franchise ?',
                'class' => Franchise::class,
                'choice_label' => 'name',
                'autocomplete' => true,
                'placeholder' => 'À qui appartient cette structure',
            ])

        ; 
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'data_class' => Structure::class,
        ]);
    }
}
