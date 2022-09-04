<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => array(
                    'label' => 'Mot de passe', 
                    'attr' => array(
                        'placeholder' => 'Saisissez un mot de passe'
                    ),
                    'help' => 'Votre mot de passe doit contenir au minimum 8 caractères',
                    'help_attr' => [
                        'class' => 'text-xs text-light'
                    ],
                ),
                'second_options' => array(
                    'label' => 'Confirmer le mot de passe', 
                    'attr' => array(
                        'placeholder' => 'Retapez le mot de passe'
                    )
                ),
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
