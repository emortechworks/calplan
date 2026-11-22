<?php

namespace App\Form;

use App\Entity\Rol;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'constraints' => [
                    new NotBlank(message: 'El nombre es obligatorio.'),
                    new Length(max: 120, maxMessage: 'El nombre no puede pasar de {{ limit }} caracteres.'),
                ],
                'attr' => ['placeholder' => 'Encargado de área'],
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción (opcional)',
                'required' => false,
                'constraints' => [
                    new Length(max: 255, maxMessage: 'La descripción no puede pasar de {{ limit }} caracteres.'),
                ],
                'attr' => ['rows' => 3, 'placeholder' => 'Funciones principales o notas del rol'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rol::class,
        ]);
    }
}
