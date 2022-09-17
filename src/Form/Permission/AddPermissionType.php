<?php

namespace App\Form\Permission;

use App\Entity\Permission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


/**
 * FORMULAIRE POUR LA CRÉATION D'UNE NOUVELLE PERMISSION
 */
class AddPermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => array(
                    'placeholder' => 'Entrez le nom de la permission',
                    'class' => 'input'
                ),
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 5,
                        'max'=> 50,
                        'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => array(
                    'placeholder' => 'Décrivez la permission'
                ),
                'help' => 'La description doit comporter au moins 10 caractères',
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 10,
                        'max'=> 255,
                        'minMessage' => 'La description doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'data_class' => Permission::class,
        ]);
    }
}
