<?php

namespace App\Form;

use App\Entity\Tienda;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TiendaType extends AbstractType
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
                'attr' => ['placeholder' => 'Tienda Centro'],
            ])
            ->add('codigo', TextType::class, [
                'label' => 'Código (opcional)',
                'required' => false,
                'constraints' => [
                    new Length(max: 30, maxMessage: 'El código no puede pasar de {{ limit }} caracteres.'),
                ],
                'attr' => ['placeholder' => 'T-001'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tienda::class,
        ]);
    }
}
