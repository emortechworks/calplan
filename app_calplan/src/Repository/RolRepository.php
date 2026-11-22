<?php

namespace App\Repository;

use App\Entity\Rol;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rol>
 */
class RolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rol::class);
    }

    /**
     * @return Rol[] activos primero, luego por nombre
     */
    public function delUsuario(User $usuario): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('r.activo', 'DESC')
            ->addOrderBy('r.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function nombreYaExiste(User $usuario, string $nombre, ?int $excluirId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.usuario = :usuario')
            ->andWhere('r.nombre = :nombre')
            ->setParameter('usuario', $usuario)
            ->setParameter('nombre', $nombre);

        if ($excluirId !== null) {
            $qb->andWhere('r.id != :id')
                ->setParameter('id', $excluirId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
