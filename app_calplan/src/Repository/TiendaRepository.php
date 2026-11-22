<?php

namespace App\Repository;

use App\Entity\Tienda;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tienda>
 */
class TiendaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tienda::class);
    }

    /**
     * @return Tienda[] activas primero, luego por nombre
     */
    public function delUsuario(User $usuario): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.roles', 'r')->addSelect('r')
            ->andWhere('t.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('t.activa', 'DESC')
            ->addOrderBy('t.nombre', 'ASC')
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
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.usuario = :usuario')
            ->andWhere('t.' . $campo . ' = :valor')
            ->setParameter('usuario', $usuario)
            ->setParameter('valor', $valor);

        if ($excluirId !== null) {
            $qb->andWhere('t.id != :id')
                ->setParameter('id', $excluirId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
