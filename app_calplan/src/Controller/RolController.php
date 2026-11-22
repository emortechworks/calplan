<?php

namespace App\Controller;

use App\Entity\Rol;
use App\Entity\User;
use App\Form\RolType;
use App\Repository\RolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/roles')]
#[IsGranted('ROLE_USER')]
class RolController extends AbstractController
{
    public function __construct(private RolRepository $rolRepository)
    {
    }

    #[Route('', name: 'app_rol_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();

        return $this->render('rol/index.html.twig', [
            'roles' => $this->rolRepository->delUsuario($usuario),
        ]);
    }

    #[Route('/nuevo', name: 'app_rol_nuevo', methods: ['GET', 'POST'])]
    public function nuevo(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rol = new Rol();
        $form = $this->createForm(RolType::class, $rol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $usuario */
            $usuario = $this->getUser();
            $rol->setUsuario($usuario);

            if ($this->intentarGuardar($form, $rol, null, $entityManager, 'creado')) {
                return $this->redirectToRoute('app_rol_index');
            }
        }

        return $this->render('rol/nuevo.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_rol_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Rol $rol, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropio($rol);

        $form = $this->createForm(RolType::class, $rol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->intentarGuardar($form, $rol, $rol->getId(), $entityManager, 'actualizado')) {
                return $this->redirectToRoute('app_rol_index');
            }
        }

        return $this->render('rol/editar.html.twig', [
            'form' => $form->createView(),
            'rol' => $rol,
        ]);
    }

    #[Route('/{id}/alternar', name: 'app_rol_alternar', methods: ['POST'])]
    public function alternar(Request $request, Rol $rol, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropio($rol);

        if (!$this->isCsrfTokenValid('alternar_rol'.$rol->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $rol->setActivo(!$rol->isActivo());
        $entityManager->flush();

        $this->addFlash('success', $rol->isActivo() ? 'Rol reactivado.' : 'Rol desactivado.');

        return $this->redirectToRoute('app_rol_index');
    }

    private function intentarGuardar(FormInterface $form, Rol $rol, ?int $excluirId, EntityManagerInterface $entityManager, string $accion): bool
    {
        /** @var User $usuario */
        $usuario = $rol->getUsuario();

        // Valida unicidad por usuario antes de persistir para mostrar error en el campo.
        if ($this->rolRepository->nombreYaExiste($usuario, (string) $rol->getNombre(), $excluirId)) {
            $form->get('nombre')->addError(new FormError('Ya tienes un rol con ese nombre.'));

            return false;
        }

        if ($rol->getDescripcion() === '') {
            $rol->setDescripcion(null);
        }

        $entityManager->persist($rol);
        $entityManager->flush();

        $this->addFlash('success', 'Rol '.$accion.'.');

        return true;
    }

    private function denegarSiNoEsPropio(Rol $rol): void
    {
        if ($rol->getUsuario() !== $this->getUser()) {
            throw $this->createNotFoundException('El rol no existe.');
        }
    }
}
