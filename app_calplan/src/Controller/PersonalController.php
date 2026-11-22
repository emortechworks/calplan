<?php

namespace App\Controller;

use App\Entity\Personal;
use App\Entity\Rol;
use App\Entity\Tienda;
use App\Entity\User;
use App\Form\PersonalType;
use App\Repository\PersonalRepository;
use App\Repository\TiendaRepository;
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
        private TiendaRepository $tiendaRepository,
    ) {
    }

    #[Route('', name: 'app_personal_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();
        $tiendas = $this->tiendaRepository->delUsuario($usuario);

        $tiendaFiltro = null;
        $tiendaId = $request->query->getInt('tienda', 0);
        if ($tiendaId > 0) {
            foreach ($tiendas as $tienda) {
                if ($tienda->getId() === $tiendaId) {
                    $tiendaFiltro = $tienda;
                    break;
                }
            }
        }

        return $this->render('personal/index.html.twig', [
            'personal' => $this->personalRepository->delUsuario($usuario, $tiendaFiltro),
            'tiendas' => $tiendas,
            'tiendaFiltro' => $tiendaFiltro,
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
        $tiendasPermitidas = $this->tiendasParaFormulario($usuario, $persona);
        $rolesPermitidosPorTienda = $this->rolesPermitidosPorTienda($tiendasPermitidas, $persona);

        $form = $this->createForm(PersonalType::class, $persona, [
            'usuario' => $usuario,
            'personal' => $persona,
        ]);
        $form->handleRequest($request);

        $seleccionPorTienda = $persona->getRolesOrdenadosPorTiendaIds();

        if ($form->isSubmitted()) {
            $gruposValidos = $this->resolverRolesPorTiendaEnviados(
                $form,
                $persona,
                $tiendasPermitidas,
                $rolesPermitidosPorTienda,
                $seleccionPorTienda,
            );

            if ($form->isValid() && $gruposValidos !== null) {
                $persona->setUsuario($usuario);
                $persona->reemplazarRolesPorTienda($gruposValidos);
                $entityManager->persist($persona);
                $entityManager->flush();

                $this->addFlash('success', 'Personal '.$accion.'.');

                return $this->redirectToRoute('app_personal_index');
            }
        }

        $tiendasSeleccionadasIds = [];
        foreach ($persona->getTiendas() as $tienda) {
            if ($tienda->getId() !== null) {
                $tiendasSeleccionadasIds[$tienda->getId()] = true;
            }
        }

        return $this->render($template, [
            'form' => $form->createView(),
            'persona' => $persona,
            'panelesPorTienda' => $this->construirPanelesParaVista(
                $tiendasPermitidas,
                $rolesPermitidosPorTienda,
                $seleccionPorTienda,
                $tiendasSeleccionadasIds,
            ),
        ]);
    }

    /**
     * Devuelve tiendas activas del usuario más las ya asignadas a la persona (indexadas por ID).
     *
     * @return array<int, Tienda>
     */
    private function tiendasParaFormulario(User $usuario, Personal $persona): array
    {
        $asignadasIds = [];
        foreach ($persona->getTiendas() as $tienda) {
            if ($tienda->getId() !== null) {
                $asignadasIds[$tienda->getId()] = true;
            }
        }

        $tiendas = [];
        foreach ($this->tiendaRepository->delUsuario($usuario) as $tienda) {
            $tiendaId = $tienda->getId();
            if ($tiendaId !== null && ($tienda->isActiva() || isset($asignadasIds[$tiendaId]))) {
                $tiendas[$tiendaId] = $tienda;
            }
        }

        return $tiendas;
    }

    /**
     * Por cada tienda permitida, reúne los roles activos asignados a esa tienda más los roles
     * que la persona ya tenía en esa tienda (aunque hayan sido desactivados después).
     *
     * @param array<int, Tienda> $tiendasPermitidas
     *
     * @return array<int, array<int, Rol>>
     */
    private function rolesPermitidosPorTienda(array $tiendasPermitidas, Personal $persona): array
    {
        $mapa = [];
        foreach ($tiendasPermitidas as $tiendaId => $tienda) {
            $mapa[$tiendaId] = [];
            foreach ($tienda->getRoles() as $rol) {
                $rolId = $rol->getId();
                if ($rolId !== null && $rol->isActivo()) {
                    $mapa[$tiendaId][$rolId] = $rol;
                }
            }
        }

        foreach ($persona->getPersonalRoles() as $asignacion) {
            $tiendaId = $asignacion->getTienda()->getId();
            $rol = $asignacion->getRol();
            $rolId = $rol->getId();
            if ($tiendaId !== null && $rolId !== null && isset($mapa[$tiendaId]) && !isset($mapa[$tiendaId][$rolId])) {
                $mapa[$tiendaId][$rolId] = $rol;
            }
        }

        return $mapa;
    }

    /**
     * Valida en servidor el JSON { tiendaId: [rolId, ...] }: cada tienda debe estar seleccionada
     * en el personal y cada rol debe pertenecer a esa tienda.
     *
     * @param array<int, Tienda>          $tiendasPermitidas
     * @param array<int, array<int, Rol>> $rolesPermitidosPorTienda
     * @param array<int, list<int>>       $seleccionPorTienda
     *
     * @return list<array{tienda: Tienda, roles: list<Rol>}>|null
     */
    private function resolverRolesPorTiendaEnviados(
        FormInterface $form,
        Personal $persona,
        array $tiendasPermitidas,
        array $rolesPermitidosPorTienda,
        array &$seleccionPorTienda,
    ): ?array {
        $crudo = trim((string) $form->get('rolesPorTienda')->getData());
        if ($crudo === '' || $crudo === '{}' || $crudo === '[]') {
            $seleccionPorTienda = [];

            return [];
        }

        $decodificado = json_decode($crudo, true);
        if (!\is_array($decodificado)) {
            $form->get('rolesPorTienda')->addError(new FormError('Selección de roles por tienda inválida.'));

            return null;
        }

        $tiendasMarcadasIds = [];
        foreach ($persona->getTiendas() as $tienda) {
            if ($tienda->getId() !== null) {
                $tiendasMarcadasIds[$tienda->getId()] = true;
            }
        }

        $grupos = [];
        $nuevaSeleccion = [];
        foreach ($decodificado as $tiendaKey => $rolIds) {
            if (!\is_int($tiendaKey) && !ctype_digit((string) $tiendaKey)) {
                $form->get('rolesPorTienda')->addError(new FormError('Tienda inválida en la asignación de roles.'));

                return null;
            }

            $tiendaId = (int) $tiendaKey;
            if (!\is_array($rolIds)) {
                $form->get('rolesPorTienda')->addError(new FormError('Selección de roles por tienda inválida.'));

                return null;
            }

            if ($rolIds === []) {
                continue;
            }

            if (!isset($tiendasPermitidas[$tiendaId]) || !isset($tiendasMarcadasIds[$tiendaId])) {
                $form->get('rolesPorTienda')->addError(new FormError('No puedes asignar roles de una tienda que no está seleccionada.'));

                return null;
            }

            $rolesTienda = [];
            $idsTienda = [];
            foreach ($rolIds as $valorRol) {
                if (!\is_int($valorRol) && !ctype_digit((string) $valorRol)) {
                    $form->get('rolesPorTienda')->addError(new FormError('Selección de roles inválida.'));

                    return null;
                }

                $rolId = (int) $valorRol;
                if (!isset($rolesPermitidosPorTienda[$tiendaId][$rolId])) {
                    $form->get('rolesPorTienda')->addError(new FormError('Uno de los roles seleccionados no pertenece a esa tienda.'));

                    return null;
                }

                if (!isset($rolesTienda[$rolId])) {
                    $rolesTienda[$rolId] = $rolesPermitidosPorTienda[$tiendaId][$rolId];
                    $idsTienda[] = $rolId;
                }
            }

            $nuevaSeleccion[$tiendaId] = $idsTienda;
            $grupos[] = [
                'tienda' => $tiendasPermitidas[$tiendaId],
                'roles' => array_values($rolesTienda),
            ];
        }

        $seleccionPorTienda = $nuevaSeleccion;

        return $grupos;
    }

    /**
     * @param array<int, Tienda>          $tiendasPermitidas
     * @param array<int, array<int, Rol>> $rolesPermitidosPorTienda
     * @param array<int, list<int>>       $seleccionPorTienda
     * @param array<int, true>            $tiendasSeleccionadasIds
     *
     * @return list<array{tienda: Tienda, visible: bool, rolesAsignados: list<Rol>, rolesDisponibles: list<Rol>}>
     */
    private function construirPanelesParaVista(
        array $tiendasPermitidas,
        array $rolesPermitidosPorTienda,
        array $seleccionPorTienda,
        array $tiendasSeleccionadasIds,
    ): array {
        $paneles = [];

        foreach ($tiendasPermitidas as $tiendaId => $tienda) {
            $permitidos = $rolesPermitidosPorTienda[$tiendaId] ?? [];
            $idsAsignados = $seleccionPorTienda[$tiendaId] ?? [];

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
                'tienda' => $tienda,
                'visible' => isset($tiendasSeleccionadasIds[$tiendaId]),
                'rolesAsignados' => $asignados,
                'rolesDisponibles' => $disponibles,
            ];
        }

        return $paneles;
    }

    private function denegarSiNoEsPropio(Personal $persona): void
    {
        if ($persona->getUsuario() !== $this->getUser()) {
            throw $this->createNotFoundException('El registro de personal no existe.');
        }
    }
}
