<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;


/**
 * Formulaire pour changer sa photo de profil
 */
class EditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('avatar', FileType::class, [
                'label' => ' ', 
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/bmp'
                        ],
                        'mimeTypesMessage' => 'Votre photo doit être au format jpg, png ou bmp.',
                    ]),
                    new NotBlank([
                        'message' => 'Vous n\'avez pas séléctionné de photo'
                    ])
                ]
            ])
            // Bouton pour sauvegarder les modifications.
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn-primary form-btn']
                ])
            // Bouton pour supprimer la photo de profil
            ->add('removeAvatar', SubmitType::class, [
                'label' => 'Supprimer',
                'attr' => ['class' => 'btn-xs btn-white remove-avatar']
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
