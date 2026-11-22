<?php

namespace App\Repository;

use App\Entity\Sucursal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sucursal>
 */
class SucursalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sucursal::class);
    }

    /**
     * @return Sucursal[] activas primero, luego por nombre
     */
    public function delUsuario(User $usuario): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.roles', 'r')->addSelect('r')
            ->andWhere('s.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('s.activa', 'DESC')
            ->addOrderBy('s.nombre', 'ASC')
            ->addOrderBy('r.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function nombreYaExiste(User $usuario, string $nombre, ?int $excluirId = null): bool
    {
        return $this->campoYaExiste('nombre', $usuario, $nombre, $excluirId);
    }

    public function codigoYaExiste(User $usuario, string $codigo, ?int $excluirId = null): bool
    {
        return $this->campoYaExiste('codigo', $usuario, $codigo, $excluirId);
    }

    private function campoYaExiste(string $campo, User $usuario, string $valor, ?int $excluirId): bool
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.usuario = :usuario')
            ->andWhere('s.'.$campo.' = :valor')
            ->setParameter('usuario', $usuario)
            ->setParameter('valor', $valor);

        if ($excluirId !== null) {
            $qb->andWhere('s.id != :id')
                ->setParameter('id', $excluirId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
