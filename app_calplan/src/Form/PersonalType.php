<?php

namespace App\Form;

use App\Entity\Personal;
use App\Entity\Tienda;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

        $tiendasAsignadasIds = [];
        foreach ($personal->getTiendas() as $tienda) {
            if ($tienda->getId() !== null) {
                $tiendasAsignadasIds[] = $tienda->getId();
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
            ->add('tiendas', EntityType::class, [
                'class' => Tienda::class,
                'label' => 'Tiendas asignadas',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (Tienda $tienda): string => $tienda->isActiva()
                    ? (string) $tienda->getNombre()
                    : $tienda->getNombre().' (desactivada)',
                // Incluye tiendas activas del usuario y las ya asignadas aunque hayan sido desactivadas.
                'query_builder' => static function (EntityRepository $er) use ($usuario, $tiendasAsignadasIds): QueryBuilder {
                    $qb = $er->createQueryBuilder('t')
                        ->andWhere('t.usuario = :usuario')
                        ->setParameter('usuario', $usuario)
                        ->orderBy('t.activa', 'DESC')
                        ->addOrderBy('t.nombre', 'ASC');

                    if ($tiendasAsignadasIds !== []) {
                        $qb->andWhere('t.activa = true OR t.id IN (:asignadas)')
                            ->setParameter('asignadas', $tiendasAsignadasIds);
                    } else {
                        $qb->andWhere('t.activa = true');
                    }

                    return $qb;
                },
            ])
            ->add('rolesOrdenados', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => implode(',', $personal->getRolesOrdenadosIds()),
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
