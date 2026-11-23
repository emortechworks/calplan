<?php

namespace App\Form;

use App\Entity\Personal;
use App\Entity\Sucursal;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

class PersonalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $usuario */
        $usuario = $options['usuario'];
        /** @var Personal $personal */
        $personal = $options['personal'];

        $sucursalesAsignadasIds = [];
        foreach ($personal->getSucursales() as $sucursal) {
            if ($sucursal->getId() !== null) {
                $sucursalesAsignadasIds[] = $sucursal->getId();
            }
        }

        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre completo',
                'constraints' => [
                    new NotBlank(message: 'El nombre es obligatorio.'),
                    new Length(max: 120, maxMessage: 'El nombre no puede pasar de {{ limit }} caracteres.'),
                ],
                'attr' => ['placeholder' => 'Ana López'],
            ])
            ->add('horasSemanales', IntegerType::class, [
                'label' => 'Horas semanales',
                'constraints' => [
                    new NotNull(message: 'Las horas semanales son obligatorias.'),
                    new Range(min: 1, max: 168, notInRangeMessage: 'Las horas semanales deben estar entre {{ min }} y {{ max }}.'),
                ],
                'attr' => ['min' => 1, 'max' => 168],
            ])
            ->add('fechaNacimiento', DateType::class, [
                'label' => 'Fecha de nacimiento (opcional)',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('finContrato', DateType::class, [
                'label' => 'Fin de contrato (opcional)',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('color', ChoiceType::class, [
                'label' => 'Color identificador (opcional)',
                'choices' => array_flip(Personal::COLORES_DISPONIBLES),
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'empty_data' => '#ffffff',
                // Impide elegir colores oscuros o fuera de la paleta clara autorizada.
                'constraints' => [
                    new Choice(
                        choices: array_keys(Personal::COLORES_DISPONIBLES),
                        message: 'Selecciona un color de la paleta disponible.',
                    ),
                ],
            ])
            ->add('sucursales', EntityType::class, [
                'class' => Sucursal::class,
                'label' => 'Sucursales asignadas',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (Sucursal $sucursal): string => $sucursal->isActiva()
                    ? (string) $sucursal->getNombre()
                    : $sucursal->getNombre().' (desactivada)',
                // Incluye sucursales activas del usuario y las ya asignadas aunque hayan sido desactivadas.
                'query_builder' => static function (EntityRepository $er) use ($usuario, $sucursalesAsignadasIds): QueryBuilder {
                    $qb = $er->createQueryBuilder('s')
                        ->andWhere('s.usuario = :usuario')
                        ->setParameter('usuario', $usuario)
                        ->orderBy('s.activa', 'DESC')
                        ->addOrderBy('s.nombre', 'ASC');

                    if ($sucursalesAsignadasIds !== []) {
                        $qb->andWhere('s.activa = true OR s.id IN (:asignadas)')
                            ->setParameter('asignadas', $sucursalesAsignadasIds);
                    } else {
                        $qb->andWhere('s.activa = true');
                    }

                    return $qb;
                },
            ])
            ->add('rolesPorSucursal', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => json_encode($personal->getRolesOrdenadosPorSucursalIds(), \JSON_FORCE_OBJECT),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
        $resolver->setRequired(['usuario', 'personal']);
        $resolver->setAllowedTypes('usuario', User::class);
        $resolver->setAllowedTypes('personal', Personal::class);
    }
}
