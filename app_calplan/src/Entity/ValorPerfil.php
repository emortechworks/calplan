<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'UNIQ_VALOR_PERFIL_PERSONAL_ATRIBUTO', columns: ['personal_id', 'atributo_id'])]
#[ORM\HasLifecycleCallbacks]
class ValorPerfil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'valoresPerfil')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Personal $personal;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AtributoPerfil $atributo;

    #[ORM\Column(length: 255)]
    private string $valor;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct(Personal $personal, AtributoPerfil $atributo, string $valor)
    {
        $this->personal = $personal;
        $this->atributo = $atributo;
        $this->valor = $valor;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function marcarActualizado(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonal(): Personal
    {
        return $this->personal;
    }

    public function getAtributo(): AtributoPerfil
    {
        return $this->atributo;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function setValor(string $valor): static
    {
        $this->valor = $valor;

        return $this;
    }

    public function getValorFormateado(): string
    {
        return $this->atributo->formatearValor($this->valor);
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
