<?php

namespace App\Repository;

use App\Entity\AtributoPerfil;
use App\Entity\User;
use App\Entity\ValorPerfil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AtributoPerfil>
 */
class AtributoPerfilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AtributoPerfil::class);
    }

    /**
     * @return AtributoPerfil[] activos primero, luego orden ascendente y nombre
     */
    public function delUsuario(User $usuario, bool $soloActivos = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('a.activo', 'DESC')
            ->addOrderBy('a.orden', 'ASC')
            ->addOrderBy('a.nombre', 'ASC');

        if ($soloActivos) {
            $qb->andWhere('a.activo = true');
        }

        return $qb->getQuery()->getResult();
    }

    public function nombreYaExiste(User $usuario, string $nombre, ?int $excluirId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.usuario = :usuario')
            ->andWhere('LOWER(a.nombre) = LOWER(:nombre)')
            ->setParameter('usuario', $usuario)
            ->setParameter('nombre', trim($nombre));

        if ($excluirId !== null) {
            $qb->andWhere('a.id != :excluirId')
                ->setParameter('excluirId', $excluirId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function tieneValoresCapturados(AtributoPerfil $atributo): bool
    {
        if ($atributo->getId() === null) {
            return false;
        }

        $total = (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(ValorPerfil::class, 'v')
            ->andWhere('v.atributo = :atributo')
            ->setParameter('atributo', $atributo)
            ->getQuery()
            ->getSingleScalarResult();

        return $total > 0;
    }
}
