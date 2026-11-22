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
     * Sincroniza los roles asignados actualizando prioridades en sitio para evitar
     * colisiones en UNIQ_PERSONAL_ROL durante un único flush() de Doctrine.
     *
     * @param list<Rol> $rolesOrdenados
     */
    public function reemplazarRolesEnOrden(array $rolesOrdenados): void
    {
        $existentesPorRolId = [];
        foreach ($this->personalRoles as $personalRol) {
            $rolId = $personalRol->getRol()->getId();
            if ($rolId !== null) {
                $existentesPorRolId[$rolId] = $personalRol;
            }
        }

        $conservados = [];
        $prioridad = 1;
        foreach ($rolesOrdenados as $rol) {
            $rolId = $rol->getId();
            if ($rolId === null || isset($conservados[$rolId])) {
                continue;
            }

            $conservados[$rolId] = true;
            if (isset($existentesPorRolId[$rolId])) {
                $existentesPorRolId[$rolId]->setPrioridad($prioridad);
            } else {
                $this->personalRoles->add(new PersonalRol($this, $rol, $prioridad));
            }
            ++$prioridad;
        }

        foreach ($existentesPorRolId as $rolId => $personalRol) {
            if (!isset($conservados[$rolId])) {
                $this->personalRoles->removeElement($personalRol);
            }
        }
    }

    /**
     * @return list<int>
     */
    public function getRolesOrdenadosIds(): array
    {
        $asignaciones = $this->personalRoles->toArray();
        usort($asignaciones, static fn (PersonalRol $a, PersonalRol $b): int => $a->getPrioridad() <=> $b->getPrioridad());

        $ids = [];
        foreach ($asignaciones as $asignacion) {
            $rolId = $asignacion->getRol()->getId();
            if ($rolId !== null) {
                $ids[] = $rolId;
            }
        }

        return $ids;
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
