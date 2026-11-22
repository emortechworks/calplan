<?php

namespace App\Controller;

use App\Entity\Personal;
use App\Entity\Rol;
use App\Entity\Tienda;
use App\Entity\User;
use App\Form\PersonalType;
use App\Repository\PersonalRepository;
use App\Repository\RolRepository;
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
        private RolRepository $rolRepository,
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
        $rolesPermitidosPorId = $this->rolesPermitidos($usuario, $persona);
        $form = $this->createForm(PersonalType::class, $persona, [
            'usuario' => $usuario,
            'personal' => $persona,
        ]);
        $form->handleRequest($request);

        $idsSeleccionados = $persona->getRolesOrdenadosIds();

        if ($form->isSubmitted()) {
            $rolesValidos = $this->resolverRolesEnviados($form, $rolesPermitidosPorId, $idsSeleccionados);

            if ($form->isValid() && $rolesValidos !== null) {
                $persona->setUsuario($usuario);
                $persona->reemplazarRolesEnOrden($rolesValidos);
                $entityManager->persist($persona);
                $entityManager->flush();

                $this->addFlash('success', 'Personal '.$accion.'.');

                return $this->redirectToRoute('app_personal_index');
            }
        }

        [$rolesAsignados, $rolesDisponibles] = $this->separarRolesParaVista($rolesPermitidosPorId, $idsSeleccionados);

        return $this->render($template, [
            'form' => $form->createView(),
            'persona' => $persona,
            'rolesAsignados' => $rolesAsignados,
            'rolesDisponibles' => $rolesDisponibles,
        ]);
    }

    /**
     * Construye el mapa de roles permitidos: roles activos del usuario más los que la persona
     * ya tiene asignados (aunque luego hayan sido desactivados) para no perderlos al editar.
     *
     * @return array<int, Rol>
     */
    private function rolesPermitidos(User $usuario, Personal $persona): array
    {
        $asignadosIds = array_flip($persona->getRolesOrdenadosIds());
        $permitidos = [];

        foreach ($this->rolRepository->delUsuario($usuario) as $rol) {
            $rolId = $rol->getId();
            if ($rolId === null) {
                continue;
            }

            if ($rol->isActivo() || isset($asignadosIds[$rolId])) {
                $permitidos[$rolId] = $rol;
            }
        }

        return $permitidos;
    }

    /**
     * Valida en servidor que todos los IDs de roles enviados pertenezcan al conjunto permitido del usuario.
     *
     * @param array<int, Rol> $rolesPermitidosPorId
     * @param list<int>       $idsSeleccionados
     *
     * @return list<Rol>|null
     */
    private function resolverRolesEnviados(FormInterface $form, array $rolesPermitidosPorId, array &$idsSeleccionados): ?array
    {
        $crudo = trim((string) $form->get('rolesOrdenados')->getData());
        if ($crudo === '') {
            $idsSeleccionados = [];

            return [];
        }

        $roles = [];
        $ids = [];
        foreach (explode(',', $crudo) as $segmento) {
            $segmento = trim($segmento);
            if ($segmento === '') {
                continue;
            }

            if (!ctype_digit($segmento)) {
                $form->get('rolesOrdenados')->addError(new FormError('Selección de roles inválida.'));

                return null;
            }

            $rolId = (int) $segmento;
            if (!isset($rolesPermitidosPorId[$rolId])) {
                $form->get('rolesOrdenados')->addError(new FormError('Uno de los roles seleccionados no es válido.'));

                return null;
            }

            if (!isset($roles[$rolId])) {
                $roles[$rolId] = $rolesPermitidosPorId[$rolId];
                $ids[] = $rolId;
            }
        }

        $idsSeleccionados = $ids;

        return array_values($roles);
    }

    /**
     * @param array<int, Rol> $rolesPermitidosPorId
     * @param list<int>       $idsSeleccionados
     *
     * @return array{0: list<Rol>, 1: list<Rol>}
     */
    private function separarRolesParaVista(array $rolesPermitidosPorId, array $idsSeleccionados): array
    {
        $asignados = [];
        $usados = [];

        foreach ($idsSeleccionados as $rolId) {
            if (isset($rolesPermitidosPorId[$rolId])) {
                $asignados[] = $rolesPermitidosPorId[$rolId];
                $usados[$rolId] = true;
            }
        }

        $disponibles = [];
        foreach ($rolesPermitidosPorId as $rolId => $rol) {
            if (!isset($usados[$rolId])) {
                $disponibles[] = $rol;
            }
        }

        return [$asignados, $disponibles];
    }

    private function denegarSiNoEsPropio(Personal $persona): void
    {
        if ($persona->getUsuario() !== $this->getUser()) {
            throw $this->createNotFoundException('El registro de personal no existe.');
        }
    }
}
