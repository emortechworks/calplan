<?php

namespace App\Entity;

use App\Repository\PersonalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Personal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $horasSemanales = 48;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fechaNacimiento = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $finContrato = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $usuario = null;

    #[ORM\Column]
    private bool $activo = true;

    /**
     * @var Collection<int, Tienda>
     */
    #[ORM\ManyToMany(targetEntity: Tienda::class)]
    #[ORM\JoinTable(name: 'personal_tienda')]
    private Collection $tiendas;

    /**
     * @var Collection<int, PersonalRol>
     */
    #[ORM\OneToMany(targetEntity: PersonalRol::class, mappedBy: 'personal', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['prioridad' => 'ASC'])]
    private Collection $personalRoles;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->tiendas = new ArrayCollection();
        $this->personalRoles = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function marcarCreado(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function marcarActualizado(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getHorasSemanales(): int
    {
        return $this->horasSemanales;
    }

    public function setHorasSemanales(int $horasSemanales): static
    {
        $this->horasSemanales = $horasSemanales;

        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(?\DateTimeImmutable $fechaNacimiento): static
    {
        $this->fechaNacimiento = $fechaNacimiento;

        return $this;
    }

    public function getFinContrato(): ?\DateTimeImmutable
    {
        return $this->finContrato;
    }

    public function setFinContrato(?\DateTimeImmutable $finContrato): static
    {
        $this->finContrato = $finContrato;

        return $this;
    }

    public function getUsuario(): ?User
    {
        return $this->usuario;
    }

    public function setUsuario(?User $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }

    /**
     * @return Collection<int, Tienda>
     */
    public function getTiendas(): Collection
    {
        return $this->tiendas;
    }

    public function addTienda(Tienda $tienda): static
    {
        if (!$this->tiendas->contains($tienda)) {
            $this->tiendas->add($tienda);
        }

        return $this;
    }

    public function removeTienda(Tienda $tienda): static
    {
        $this->tiendas->removeElement($tienda);

        return $this;
    }

    /**
     * @return Collection<int, PersonalRol>
     */
    public function getPersonalRoles(): Collection
    {
        return $this->personalRoles;
    }

    /**
     * Sincroniza los roles por tienda actualizando prioridades en sitio para evitar
     * colisiones en UNIQ_PERSONAL_TIENDA_ROL durante un único flush() de Doctrine.
     *
     * @param list<array{tienda: Tienda, roles: list<Rol>}> $rolesPorTienda
     */
    public function reemplazarRolesPorTienda(array $rolesPorTienda): void
    {
        $existentesPorClave = [];
        foreach ($this->personalRoles as $personalRol) {
            $tiendaId = $personalRol->getTienda()->getId();
            $rolId = $personalRol->getRol()->getId();
            if ($tiendaId !== null && $rolId !== null) {
                $existentesPorClave[$tiendaId.':'.$rolId] = $personalRol;
            }
        }

        $conservados = [];
        foreach ($rolesPorTienda as $grupo) {
            $tienda = $grupo['tienda'];
            $tiendaId = $tienda->getId();
            if ($tiendaId === null) {
                continue;
            }

            $prioridad = 1;
            foreach ($grupo['roles'] as $rol) {
                $rolId = $rol->getId();
                if ($rolId === null) {
                    continue;
                }

                $clave = $tiendaId.':'.$rolId;
                if (isset($conservados[$clave])) {
                    continue;
                }

                $conservados[$clave] = true;
                if (isset($existentesPorClave[$clave])) {
                    $existentesPorClave[$clave]->setPrioridad($prioridad);
                } else {
                    $this->personalRoles->add(new PersonalRol($this, $tienda, $rol, $prioridad));
                }
                ++$prioridad;
            }
        }

        foreach ($existentesPorClave as $clave => $personalRol) {
            if (!isset($conservados[$clave])) {
                $this->personalRoles->removeElement($personalRol);
            }
        }
    }

    /**
     * @return array<int, list<int>> mapa tiendaId => lista ordenada de rolIds por prioridad
     */
    public function getRolesOrdenadosPorTiendaIds(): array
    {
        $asignaciones = $this->personalRoles->toArray();
        usort($asignaciones, static fn (PersonalRol $a, PersonalRol $b): int => $a->getPrioridad() <=> $b->getPrioridad());

        $mapa = [];
        foreach ($asignaciones as $asignacion) {
            $tiendaId = $asignacion->getTienda()->getId();
            $rolId = $asignacion->getRol()->getId();
            if ($tiendaId !== null && $rolId !== null) {
                $mapa[$tiendaId][] = $rolId;
            }
        }

        return $mapa;
    }

    /**
     * @return list<PersonalRol>
     */
    public function getRolesDeTienda(Tienda $tienda): array
    {
        $resultado = [];
        foreach ($this->personalRoles as $asignacion) {
            if ($asignacion->getTienda()->getId() === $tienda->getId()) {
                $resultado[] = $asignacion;
            }
        }

        usort($resultado, static fn (PersonalRol $a, PersonalRol $b): int => $a->getPrioridad() <=> $b->getPrioridad());

        return $resultado;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
