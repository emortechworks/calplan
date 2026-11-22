<?php

namespace App\Repository;

use App\Entity\Personal;
use App\Entity\Tienda;
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
    public function delUsuario(User $usuario, ?Tienda $tienda = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.tiendas', 't')->addSelect('t')
            ->leftJoin('p.personalRoles', 'pr')->addSelect('pr')
            ->leftJoin('pr.tienda', 'prt')->addSelect('prt')
            ->leftJoin('pr.rol', 'r')->addSelect('r')
            ->andWhere('p.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('p.activo', 'DESC')
            ->addOrderBy('p.nombre', 'ASC')
            ->addOrderBy('pr.prioridad', 'ASC');

        if ($tienda !== null) {
            $qb->andWhere(':tiendaFiltro MEMBER OF p.tiendas')
                ->setParameter('tiendaFiltro', $tienda);
        }

        return $qb->getQuery()->getResult();
    }
}
