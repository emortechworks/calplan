<?php

namespace App\Form;

use App\Entity\Rol;
use App\Entity\Tienda;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
        /** @var User $usuario */
        $usuario = $options['usuario'];
        /** @var Tienda $tienda */
        $tienda = $options['tienda'];

        $rolesAsignadosIds = [];
        foreach ($tienda->getRoles() as $rol) {
            if ($rol->getId() !== null) {
                $rolesAsignadosIds[] = $rol->getId();
            }
        }

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
            ->add('roles', EntityType::class, [
                'class' => Rol::class,
                'label' => 'Roles disponibles en esta tienda',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (Rol $rol): string => $rol->isActivo()
                    ? (string) $rol->getNombre()
                    : $rol->getNombre().' (desactivado)',
                // Incluye roles activos del usuario y los ya asignados a esta tienda aunque hayan sido desactivados.
                'query_builder' => static function (EntityRepository $er) use ($usuario, $rolesAsignadosIds): QueryBuilder {
                    $qb = $er->createQueryBuilder('r')
                        ->andWhere('r.usuario = :usuario')
                        ->setParameter('usuario', $usuario)
                        ->orderBy('r.activo', 'DESC')
                        ->addOrderBy('r.nombre', 'ASC');

                    if ($rolesAsignadosIds !== []) {
                        $qb->andWhere('r.activo = true OR r.id IN (:asignados)')
                            ->setParameter('asignados', $rolesAsignadosIds);
                    } else {
                        $qb->andWhere('r.activo = true');
                    }

                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tienda::class,
        ]);
        $resolver->setRequired(['usuario', 'tienda']);
        $resolver->setAllowedTypes('usuario', User::class);
        $resolver->setAllowedTypes('tienda', Tienda::class);
    }
}
