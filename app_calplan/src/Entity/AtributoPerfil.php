<?php

namespace App\Entity;

use App\Repository\AtributoPerfilRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AtributoPerfilRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_ATRIBUTO_PERFIL_USUARIO_NOMBRE', columns: ['usuario_id', 'nombre'])]
#[ORM\HasLifecycleCallbacks]
class AtributoPerfil
{
    public const TIPO_ESCALA = 'escala';
    public const TIPO_BOOLEANO = 'booleano';
    public const TIPO_LISTA = 'lista';
    public const TIPO_NUMERO = 'numero';
    public const TIPO_TEXTO = 'texto';

    /**
     * @var array<string, string>
     */
    public const TIPOS = [
        self::TIPO_ESCALA => 'Escala (1 a 5)',
        self::TIPO_BOOLEANO => 'Sí / No',
        self::TIPO_LISTA => 'Lista de opciones',
        self::TIPO_NUMERO => 'Número',
        self::TIPO_TEXTO => 'Texto corto',
    ];

    /**
     * Catálogo inicial descriptivo sugerido por usuario (sin peso en la planeación).
     *
     * @var list<array{nombre: string, tipo: string, opciones: list<string>|null, orden: int}>
     */
    public const SUGERIDOS = [
        ['nombre' => 'Velocidad', 'tipo' => self::TIPO_ESCALA, 'opciones' => null, 'orden' => 1],
        ['nombre' => 'Fuerza', 'tipo' => self::TIPO_ESCALA, 'opciones' => null, 'orden' => 2],
        ['nombre' => 'Puntualidad', 'tipo' => self::TIPO_ESCALA, 'opciones' => null, 'orden' => 3],
        ['nombre' => 'Confiabilidad', 'tipo' => self::TIPO_ESCALA, 'opciones' => null, 'orden' => 4],
        ['nombre' => 'Sexo', 'tipo' => self::TIPO_LISTA, 'opciones' => ['Femenino', 'Masculino', 'Otro'], 'orden' => 5],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $usuario = null;

    #[ORM\Column(length: 80)]
    private ?string $nombre = null;

    #[ORM\Column(length: 20)]
    private string $tipo = self::TIPO_ESCALA;

    /**
     * @var list<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $opciones = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $orden = 0;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getUsuario(): ?User
    {
        return $this->usuario;
    }

    public function setUsuario(?User $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = trim($nombre);

        return $this;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getEtiquetaTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /**
     * @return list<string>
     */
    public function getOpciones(): array
    {
        return $this->opciones ?? [];
    }

    /**
     * @param list<string>|null $opciones
     */
    public function setOpciones(?array $opciones): static
    {
        $this->opciones = $opciones !== null && $opciones !== [] ? array_values($opciones) : null;

        return $this;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): static
    {
        $this->orden = $orden;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function formatearValor(string $valor): string
    {
        return match ($this->tipo) {
            self::TIPO_ESCALA => $valor.'/5',
            self::TIPO_BOOLEANO => $valor === '1' ? 'Sí' : 'No',
            default => $valor,
        };
    }

    /**
     * Normaliza texto de opciones separadas por saltos de línea o comas, sin vacíos ni duplicados.
     *
     * @return list<string>
     */
    public static function normalizarOpciones(string $texto): array
    {
        $partes = preg_split('/[\r\n,]+/', $texto) ?: [];
        $resultado = [];
        $vistos = [];

        foreach ($partes as $parte) {
            $limpia = trim($parte);
            if ($limpia === '') {
                continue;
            }

            $clave = mb_strtolower($limpia);
            if (!isset($vistos[$clave])) {
                $vistos[$clave] = true;
                $resultado[] = $limpia;
            }
        }

        return $resultado;
    }
}
