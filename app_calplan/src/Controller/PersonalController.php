<?php

namespace App\Controller;

use App\Entity\AtributoPerfil;
use App\Entity\Personal;
use App\Entity\Rol;
use App\Entity\Sucursal;
use App\Entity\User;
use App\Form\PersonalType;
use App\Repository\AtributoPerfilRepository;
use App\Repository\PersonalRepository;
use App\Repository\SucursalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/personal')]
#[IsGranted('ROLE_USER')]
class PersonalController extends AbstractController
{
    public function __construct(
        private PersonalRepository $personalRepository,
        private SucursalRepository $sucursalRepository,
        private AtributoPerfilRepository $atributoPerfilRepository,
    ) {
    }

    #[Route('', name: 'app_personal_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();
        $sucursales = $this->sucursalRepository->delUsuario($usuario);

        $sucursalFiltro = null;
        $sucursalId = $request->query->getInt('sucursal', 0);
        if ($sucursalId > 0) {
            foreach ($sucursales as $sucursal) {
                if ($sucursal->getId() === $sucursalId) {
                    $sucursalFiltro = $sucursal;
                    break;
                }
            }
        }

        return $this->render('personal/index.html.twig', [
            'personal' => $this->personalRepository->delUsuario($usuario, $sucursalFiltro),
            'sucursales' => $sucursales,
            'sucursalFiltro' => $sucursalFiltro,
        ]);
    }

    #[Route('/nuevo', name: 'app_personal_nuevo', methods: ['GET', 'POST'])]
    public function nuevo(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();
        $persona = new Personal();

        return $this->procesarFormulario($request, $usuario, $persona, $entityManager, 'personal/nuevo.html.twig', 'creado');
    }

    #[Route('/{id}/editar', name: 'app_personal_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Personal $persona, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropio($persona);

        /** @var User $usuario */
        $usuario = $this->getUser();

        return $this->procesarFormulario($request, $usuario, $persona, $entityManager, 'personal/editar.html.twig', 'actualizado');
    }

    #[Route('/{id}/alternar', name: 'app_personal_alternar', methods: ['POST'])]
    public function alternar(Request $request, Personal $persona, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropio($persona);

        if (!$this->isCsrfTokenValid('alternar_personal'.$persona->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $persona->setActivo(!$persona->isActivo());
        $entityManager->flush();

        $this->addFlash('success', $persona->isActivo() ? 'Personal reactivado.' : 'Personal desactivado.');

        return $this->redirectToRoute('app_personal_index');
    }

    private function procesarFormulario(
        Request $request,
        User $usuario,
        Personal $persona,
        EntityManagerInterface $entityManager,
        string $template,
        string $accion,
    ): Response {
        $sucursalesPermitidas = $this->sucursalesParaFormulario($usuario, $persona);
        $rolesPermitidosPorSucursal = $this->rolesPermitidosPorSucursal($sucursalesPermitidas, $persona);
        $atributosPerfil = $this->atributosParaFormulario($usuario, $persona);

        $form = $this->createForm(PersonalType::class, $persona, [
            'usuario' => $usuario,
            'personal' => $persona,
        ]);
        $form->handleRequest($request);

        $seleccionPorSucursal = $persona->getRolesOrdenadosPorSucursalIds();
        $valoresPerfil = $persona->getMapaValoresPerfil();
        $erroresPerfil = [];

        if ($form->isSubmitted()) {
            $gruposValidos = $this->resolverRolesPorSucursalEnviados(
                $form,
                $persona,
                $sucursalesPermitidas,
                $rolesPermitidosPorSucursal,
                $seleccionPorSucursal,
            );
            $valoresPerfilValidos = $this->resolverValoresPerfilEnviados(
                $request->request->all('perfil'),
                $form,
                $atributosPerfil,
                $valoresPerfil,
                $erroresPerfil,
            );

            if ($form->isValid() && $gruposValidos !== null && $valoresPerfilValidos !== null) {
                $persona->setUsuario($usuario);
                $persona->reemplazarRolesPorSucursal($gruposValidos);
                $persona->reemplazarValoresPerfil($valoresPerfilValidos);
                $entityManager->persist($persona);
                $entityManager->flush();

                $this->addFlash('success', 'Personal '.$accion.'.');

                return $this->redirectToRoute('app_personal_index');
            }
        }

        $sucursalesSeleccionadasIds = [];
        foreach ($persona->getSucursales() as $sucursal) {
            if ($sucursal->getId() !== null) {
                $sucursalesSeleccionadasIds[$sucursal->getId()] = true;
            }
        }

        return $this->render($template, [
            'form' => $form->createView(),
            'persona' => $persona,
            'panelesPorSucursal' => $this->construirPanelesParaVista(
                $sucursalesPermitidas,
                $rolesPermitidosPorSucursal,
                $seleccionPorSucursal,
                $sucursalesSeleccionadasIds,
            ),
            'atributosPerfil' => array_values($atributosPerfil),
            'valoresPerfil' => $valoresPerfil,
            'erroresPerfil' => $erroresPerfil,
        ]);
    }

    /**
     * Devuelve sucursales activas del usuario más las ya asignadas a la persona (indexadas por ID).
     *
     * @return array<int, Sucursal>
     */
    private function sucursalesParaFormulario(User $usuario, Personal $persona): array
    {
        $asignadasIds = [];
        foreach ($persona->getSucursales() as $sucursal) {
            if ($sucursal->getId() !== null) {
                $asignadasIds[$sucursal->getId()] = true;
            }
        }

        $sucursales = [];
        foreach ($this->sucursalRepository->delUsuario($usuario) as $sucursal) {
            $sucursalId = $sucursal->getId();
            if ($sucursalId !== null && ($sucursal->isActiva() || isset($asignadasIds[$sucursalId]))) {
                $sucursales[$sucursalId] = $sucursal;
            }
        }

        return $sucursales;
    }

    /**
     * Por cada sucursal permitida, reúne los roles activos asignados a esa sucursal más los roles
     * que la persona ya tenía en esa sucursal (aunque hayan sido desactivados después).
     *
     * @param array<int, Sucursal> $sucursalesPermitidas
     *
     * @return array<int, array<int, Rol>>
     */
    private function rolesPermitidosPorSucursal(array $sucursalesPermitidas, Personal $persona): array
    {
        $mapa = [];
        foreach ($sucursalesPermitidas as $sucursalId => $sucursal) {
            $mapa[$sucursalId] = [];
            foreach ($sucursal->getRoles() as $rol) {
                $rolId = $rol->getId();
                if ($rolId !== null && $rol->isActivo()) {
                    $mapa[$sucursalId][$rolId] = $rol;
                }
            }
        }

        foreach ($persona->getPersonalRoles() as $asignacion) {
            $sucursalId = $asignacion->getSucursal()->getId();
            $rol = $asignacion->getRol();
            $rolId = $rol->getId();
            if ($sucursalId !== null && $rolId !== null && isset($mapa[$sucursalId]) && !isset($mapa[$sucursalId][$rolId])) {
                $mapa[$sucursalId][$rolId] = $rol;
            }
        }

        return $mapa;
    }

    /**
     * Valida en servidor el JSON { sucursalId: [rolId, ...] }: cada sucursal debe estar seleccionada
     * en el personal y cada rol debe pertenecer a esa sucursal.
     *
     * @param array<int, Sucursal>        $sucursalesPermitidas
     * @param array<int, array<int, Rol>> $rolesPermitidosPorSucursal
     * @param array<int, list<int>>       $seleccionPorSucursal
     *
     * @return list<array{sucursal: Sucursal, roles: list<Rol>}>|null
     */
    private function resolverRolesPorSucursalEnviados(
        FormInterface $form,
        Personal $persona,
        array $sucursalesPermitidas,
        array $rolesPermitidosPorSucursal,
        array &$seleccionPorSucursal,
    ): ?array {
        $crudo = trim((string) $form->get('rolesPorSucursal')->getData());
        if ($crudo === '' || $crudo === '{}' || $crudo === '[]') {
            $seleccionPorSucursal = [];

            return [];
        }

        $decodificado = json_decode($crudo, true);
        if (!\is_array($decodificado)) {
            $form->get('rolesPorSucursal')->addError(new FormError('Selección de roles por sucursal inválida.'));

            return null;
        }

        $sucursalesMarcadasIds = [];
        foreach ($persona->getSucursales() as $sucursal) {
            if ($sucursal->getId() !== null) {
                $sucursalesMarcadasIds[$sucursal->getId()] = true;
            }
        }

        $grupos = [];
        $nuevaSeleccion = [];
        foreach ($decodificado as $sucursalKey => $rolIds) {
            if (!\is_int($sucursalKey) && !ctype_digit((string) $sucursalKey)) {
                $form->get('rolesPorSucursal')->addError(new FormError('Sucursal inválida en la asignación de roles.'));

                return null;
            }

            $sucursalId = (int) $sucursalKey;
            if (!\is_array($rolIds)) {
                $form->get('rolesPorSucursal')->addError(new FormError('Selección de roles por sucursal inválida.'));

                return null;
            }

            if ($rolIds === []) {
                continue;
            }

            if (!isset($sucursalesPermitidas[$sucursalId]) || !isset($sucursalesMarcadasIds[$sucursalId])) {
                $form->get('rolesPorSucursal')->addError(new FormError('No puedes asignar roles de una sucursal que no está seleccionada.'));

                return null;
            }

            $rolesSucursal = [];
            $idsSucursal = [];
            foreach ($rolIds as $valorRol) {
                if (!\is_int($valorRol) && !ctype_digit((string) $valorRol)) {
                    $form->get('rolesPorSucursal')->addError(new FormError('Selección de roles inválida.'));

                    return null;
                }

                $rolId = (int) $valorRol;
                if (!isset($rolesPermitidosPorSucursal[$sucursalId][$rolId])) {
                    $form->get('rolesPorSucursal')->addError(new FormError('Uno de los roles seleccionados no pertenece a esa sucursal.'));

                    return null;
                }

                if (!isset($rolesSucursal[$rolId])) {
                    $rolesSucursal[$rolId] = $rolesPermitidosPorSucursal[$sucursalId][$rolId];
                    $idsSucursal[] = $rolId;
                }
            }

            $nuevaSeleccion[$sucursalId] = $idsSucursal;
            $grupos[] = [
                'sucursal' => $sucursalesPermitidas[$sucursalId],
                'roles' => array_values($rolesSucursal),
            ];
        }

        $seleccionPorSucursal = $nuevaSeleccion;

        return $grupos;
    }

    /**
     * @param array<int, Sucursal>        $sucursalesPermitidas
     * @param array<int, array<int, Rol>> $rolesPermitidosPorSucursal
     * @param array<int, list<int>>       $seleccionPorSucursal
     * @param array<int, true>            $sucursalesSeleccionadasIds
     *
     * @return list<array{sucursal: Sucursal, visible: bool, rolesAsignados: list<Rol>, rolesDisponibles: list<Rol>}>
     */
    private function construirPanelesParaVista(
        array $sucursalesPermitidas,
        array $rolesPermitidosPorSucursal,
        array $seleccionPorSucursal,
        array $sucursalesSeleccionadasIds,
    ): array {
        $paneles = [];

        foreach ($sucursalesPermitidas as $sucursalId => $sucursal) {
            $permitidos = $rolesPermitidosPorSucursal[$sucursalId] ?? [];
            $idsAsignados = $seleccionPorSucursal[$sucursalId] ?? [];

            $asignados = [];
            $usados = [];
            foreach ($idsAsignados as $rolId) {
                if (isset($permitidos[$rolId])) {
                    $asignados[] = $permitidos[$rolId];
                    $usados[$rolId] = true;
                }
            }

            $disponibles = [];
            foreach ($permitidos as $rolId => $rol) {
                if (!isset($usados[$rolId])) {
                    $disponibles[] = $rol;
                }
            }

            $paneles[] = [
                'sucursal' => $sucursal,
                'visible' => isset($sucursalesSeleccionadasIds[$sucursalId]),
                'rolesAsignados' => $asignados,
                'rolesDisponibles' => $disponibles,
            ];
        }

        return $paneles;
    }

    /**
     * Reúne atributos activos del usuario más los desactivados que la persona ya tenga con valor.
     *
     * @return array<int, AtributoPerfil>
     */
    private function atributosParaFormulario(User $usuario, Personal $persona): array
    {
        $conValorIds = $persona->getMapaValoresPerfil();
        $resultado = [];

        foreach ($this->atributoPerfilRepository->delUsuario($usuario) as $atributo) {
            $atributoId = $atributo->getId();
            if ($atributoId !== null && ($atributo->isActivo() || isset($conValorIds[$atributoId]))) {
                $resultado[$atributoId] = $atributo;
            }
        }

        return $resultado;
    }

    /**
     * Valida en servidor los valores de perfil enviados por tipo y rechaza atributos ajenos.
     *
     * @param array<int|string, mixed>   $enviados
     * @param array<int, AtributoPerfil> $atributosPermitidos
     * @param array<int, string>         $valoresVista
     * @param array<int, string>         $erroresPerfil
     *
     * @return list<array{atributo: AtributoPerfil, valor: string}>|null
     */
    private function resolverValoresPerfilEnviados(
        array $enviados,
        FormInterface $form,
        array $atributosPermitidos,
        array &$valoresVista,
        array &$erroresPerfil,
    ): ?array {
        $hayErrores = false;

        foreach ($enviados as $clave => $valorCrudo) {
            $valor = \is_scalar($valorCrudo) ? trim((string) $valorCrudo) : '';
            if ($valor === '') {
                continue;
            }

            if ((!\is_int($clave) && !ctype_digit((string) $clave)) || !isset($atributosPermitidos[(int) $clave])) {
                $form->addError(new FormError('Uno de los atributos de perfil enviados no es válido.'));

                return null;
            }
        }

        $pares = [];
        $nuevosValoresVista = [];

        foreach ($atributosPermitidos as $atributoId => $atributo) {
            $crudo = isset($enviados[$atributoId]) && \is_scalar($enviados[$atributoId])
                ? trim((string) $enviados[$atributoId])
                : '';

            if ($crudo === '') {
                continue;
            }

            $nuevosValoresVista[$atributoId] = $crudo;
            $error = $this->validarValorSegunTipo($atributo, $crudo);
            if ($error !== null) {
                $erroresPerfil[$atributoId] = $error;
                $hayErrores = true;
                continue;
            }

            $pares[] = [
                'atributo' => $atributo,
                'valor' => $crudo,
            ];
        }

        $valoresVista = $nuevosValoresVista;

        if ($hayErrores) {
            $form->addError(new FormError('Revisa los valores del perfil descriptivo.'));

            return null;
        }

        return $pares;
    }

    private function validarValorSegunTipo(AtributoPerfil $atributo, string $valor): ?string
    {
        return match ($atributo->getTipo()) {
            AtributoPerfil::TIPO_ESCALA => \in_array($valor, ['1', '2', '3', '4', '5'], true)
                ? null
                : 'Selecciona un valor entre 1 y 5.',
            AtributoPerfil::TIPO_BOOLEANO => \in_array($valor, ['0', '1'], true)
                ? null
                : 'Selecciona Sí o No.',
            AtributoPerfil::TIPO_LISTA => \in_array($valor, $atributo->getOpciones(), true)
                ? null
                : 'Selecciona una opción válida de la lista.',
            AtributoPerfil::TIPO_NUMERO => (is_numeric($valor) && mb_strlen($valor) <= 30)
                ? null
                : 'Ingresa un número válido.',
            AtributoPerfil::TIPO_TEXTO => mb_strlen($valor) <= 120
                ? null
                : 'El texto no puede pasar de 120 caracteres.',
            default => 'Tipo de atributo no soportado.',
        };
    }

    private function denegarSiNoEsPropio(Personal $persona): void
    {
        if ($persona->getUsuario() !== $this->getUser()) {
            throw $this->createNotFoundException('El registro de personal no existe.');
        }
    }
}
