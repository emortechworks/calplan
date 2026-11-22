<?php

namespace App\Form;

use App\Entity\Rol;
use App\Entity\Sucursal;
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

class SucursalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $usuario */
        $usuario = $options['usuario'];
        /** @var Sucursal $sucursal */
        $sucursal = $options['sucursal'];

        $rolesAsignadosIds = [];
        foreach ($sucursal->getRoles() as $rol) {
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
                'attr' => ['placeholder' => 'Sucursal Centro'],
            ])
            ->add('codigo', TextType::class, [
                'label' => 'Código (opcional)',
                'required' => false,
                'constraints' => [
                    new Length(max: 30, maxMessage: 'El código no puede pasar de {{ limit }} caracteres.'),
                ],
                'attr' => ['placeholder' => 'S-001'],
            ])
            ->add('roles', EntityType::class, [
                'class' => Rol::class,
                'label' => 'Roles disponibles en esta sucursal',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (Rol $rol): string => $rol->isActivo()
                    ? (string) $rol->getNombre()
                    : $rol->getNombre().' (desactivado)',
                // Incluye roles activos del usuario y los ya asignados a esta sucursal aunque hayan sido desactivados.
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
            'data_class' => Sucursal::class,
        ]);
        $resolver->setRequired(['usuario', 'sucursal']);
        $resolver->setAllowedTypes('usuario', User::class);
        $resolver->setAllowedTypes('sucursal', Sucursal::class);
    }
}
