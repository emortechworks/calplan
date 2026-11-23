<?php

namespace App\Form;

use App\Entity\AtributoPerfil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

class AtributoPerfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AtributoPerfil $atributo */
        $atributo = $options['data'];
        $tipoBloqueado = (bool) $options['tipo_bloqueado'];

        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del atributo',
                'constraints' => [
                    new NotBlank(message: 'El nombre es obligatorio.'),
                    new Length(max: 80, maxMessage: 'El nombre no puede pasar de {{ limit }} caracteres.'),
                ],
                'attr' => ['placeholder' => 'Velocidad, Puntualidad, Turno preferido…'],
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo de dato',
                'choices' => array_flip(AtributoPerfil::TIPOS),
                'disabled' => $tipoBloqueado,
                'constraints' => [
                    new Choice(
                        choices: array_keys(AtributoPerfil::TIPOS),
                        message: 'Selecciona un tipo de dato válido.',
                    ),
                ],
            ])
            ->add('opcionesTexto', TextareaType::class, [
                'label' => 'Opciones de la lista (una por línea o separadas por coma)',
                'mapped' => false,
                'required' => false,
                'data' => implode("\n", $atributo->getOpciones()),
                'attr' => [
                    'rows' => 4,
                    'placeholder' => "Femenino\nMasculino\nOtro",
                ],
            ])
            ->add('orden', IntegerType::class, [
                'label' => 'Orden de aparición',
                'constraints' => [
                    new NotNull(message: 'El orden es obligatorio.'),
                    new Range(min: 0, max: 999, notInRangeMessage: 'El orden debe estar entre {{ min }} y {{ max }}.'),
                ],
                'attr' => ['min' => 0, 'max' => 999],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AtributoPerfil::class,
            'tipo_bloqueado' => false,
        ]);
        $resolver->setAllowedTypes('tipo_bloqueado', 'bool');
    }
}
