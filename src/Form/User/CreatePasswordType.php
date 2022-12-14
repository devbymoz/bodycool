<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


/**
 * FORMULAIRE POUR LA CRÉATION D'UN NOUVEAU MOT DE PASSE UTILISATEUR
 */
class CreatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'Mot de passe', 
                    'attr' => [
                        'placeholder' => 'Saisissez un mot de passe',
                        'class' => 'input'
                    ],
                    'help' => 'Votre mot de passe doit contenir au minimum 8 caractères'
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe', 
                    'attr' => [
                        'placeholder' => 'Retapez le mot de passe',
                        'class' => 'input'
                    ]
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 8,
                        'max'=> 4096,
                        'minMessage' => 'Votre mot de passe doit comporter 8 caractères minimum',
                        'maxMessage' => 'Votre mot de passe doit comporter 4096 caractères maximum'
                    ]),

                ],
            ])
        ;
    }

    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'data_class' => User::class
        ]);
    }
}
