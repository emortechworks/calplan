<?php

namespace App\Repository;

use App\Entity\Personal;
use App\Entity\Sucursal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Personal>
 */
class PersonalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personal::class);
    }

    /**
     * @return Personal[] activos primero, luego por nombre
     */
    public function delUsuario(User $usuario, ?Sucursal $sucursal = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.sucursales', 's')->addSelect('s')
            ->leftJoin('p.personalRoles', 'pr')->addSelect('pr')
            ->leftJoin('pr.sucursal', 'prs')->addSelect('prs')
            ->leftJoin('pr.rol', 'r')->addSelect('r')
            ->andWhere('p.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('p.activo', 'DESC')
            ->addOrderBy('p.nombre', 'ASC')
            ->addOrderBy('pr.prioridad', 'ASC');

        if ($sucursal !== null) {
            $qb->andWhere(':sucursalFiltro MEMBER OF p.sucursales')
                ->setParameter('sucursalFiltro', $sucursal);
        }

        return $qb->getQuery()->getResult();
    }
}
