<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'personal_rol')]
#[ORM\UniqueConstraint(name: 'UNIQ_PERSONAL_ROL', columns: ['personal_id', 'rol_id'])]
class PersonalRol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'personalRoles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Personal $personal;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Rol $rol;

    /**
     * 1 representa el mayor peso (rol principal); valores mayores indican menor prioridad.
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $prioridad;

    public function __construct(Personal $personal, Rol $rol, int $prioridad)
    {
        $this->personal = $personal;
        $this->rol = $rol;
        $this->prioridad = $prioridad;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonal(): Personal
    {
        return $this->personal;
    }

    public function getRol(): Rol
    {
        return $this->rol;
    }

    public function getPrioridad(): int
    {
        return $this->prioridad;
    }

    public function setPrioridad(int $prioridad): static
    {
        $this->prioridad = $prioridad;

        return $this;
    }
}
