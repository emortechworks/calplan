<?php

namespace App\Entity;

use App\Repository\TiendaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TiendaRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_TIENDA_NOMBRE', columns: ['usuario_id', 'nombre'])]
#[ORM\UniqueConstraint(name: 'UNIQ_TIENDA_CODIGO', columns: ['usuario_id', 'codigo'])]
#[ORM\HasLifecycleCallbacks]
class Tienda
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $nombre = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $codigo = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $usuario = null;

    #[ORM\Column]
    private bool $activa = true;

    /**
     * @var Collection<int, Rol>
     */
    #[ORM\ManyToMany(targetEntity: Rol::class)]
    #[ORM\JoinTable(name: 'tienda_rol')]
    #[ORM\OrderBy(['nombre' => 'ASC'])]
    private Collection $roles;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function marcarCreada(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function marcarActualizada(): void
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

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(?string $codigo): static
    {
        $this->codigo = $codigo;

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

    public function isActiva(): bool
    {
        return $this->activa;
    }

    public function setActiva(bool $activa): static
    {
        $this->activa = $activa;

        return $this;
    }

    /**
     * @return Collection<int, Rol>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRol(Rol $rol): static
    {
        if (!$this->roles->contains($rol)) {
            $this->roles->add($rol);
        }

        return $this;
    }

    public function removeRol(Rol $rol): static
    {
        $this->roles->removeElement($rol);

        return $this;
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
