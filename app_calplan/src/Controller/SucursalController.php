<?php

namespace App\Controller;

use App\Entity\Sucursal;
use App\Entity\User;
use App\Form\SucursalType;
use App\Repository\SucursalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sucursales')]
#[IsGranted('ROLE_USER')]
class SucursalController extends AbstractController
{
    public function __construct(private SucursalRepository $sucursalRepository)
    {
    }

    #[Route('', name: 'app_sucursal_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();

        return $this->render('sucursal/index.html.twig', [
            'sucursales' => $this->sucursalRepository->delUsuario($usuario),
        ]);
    }

    #[Route('/nueva', name: 'app_sucursal_nueva', methods: ['GET', 'POST'])]
    public function nueva(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();
        $sucursal = new Sucursal();
        $form = $this->createForm(SucursalType::class, $sucursal, [
            'usuario' => $usuario,
            'sucursal' => $sucursal,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sucursal->setUsuario($usuario);
            if ($this->intentarGuardar($form, $sucursal, null, $entityManager, 'creada')) {
                return $this->redirectToRoute('app_sucursal_index');
            }
        }

        return $this->render('sucursal/nueva.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_sucursal_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Sucursal $sucursal, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropia($sucursal);

        /** @var User $usuario */
        $usuario = $this->getUser();
        $form = $this->createForm(SucursalType::class, $sucursal, [
            'usuario' => $usuario,
            'sucursal' => $sucursal,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->intentarGuardar($form, $sucursal, $sucursal->getId(), $entityManager, 'actualizada')) {
                return $this->redirectToRoute('app_sucursal_index');
            }
        }

        return $this->render('sucursal/editar.html.twig', [
            'form' => $form->createView(),
            'sucursal' => $sucursal,
        ]);
    }

    #[Route('/{id}/alternar', name: 'app_sucursal_alternar', methods: ['POST'])]
    public function alternar(Request $request, Sucursal $sucursal, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropia($sucursal);

        if (!$this->isCsrfTokenValid('alternar_sucursal'.$sucursal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $sucursal->setActiva(!$sucursal->isActiva());
        $entityManager->flush();

        $this->addFlash('success', $sucursal->isActiva() ? 'Sucursal reactivada.' : 'Sucursal desactivada.');

        return $this->redirectToRoute('app_sucursal_index');
    }

    private function intentarGuardar(FormInterface $form, Sucursal $sucursal, ?int $excluirId, EntityManagerInterface $entityManager, string $accion): bool
    {
        /** @var User $usuario */
        $usuario = $sucursal->getUsuario();

        if ($this->sucursalRepository->nombreYaExiste($usuario, (string) $sucursal->getNombre(), $excluirId)) {
            $form->get('nombre')->addError(new FormError('Ya tienes una sucursal con ese nombre.'));

            return false;
        }

        $codigo = $sucursal->getCodigo();
        if ($codigo !== null && $codigo !== '') {
            if ($this->sucursalRepository->codigoYaExiste($usuario, $codigo, $excluirId)) {
                $form->get('codigo')->addError(new FormError('Ya tienes una sucursal con ese código.'));

                return false;
            }
        } else {
            $sucursal->setCodigo(null);
        }

        $entityManager->persist($sucursal);
        $entityManager->flush();

        $this->addFlash('success', 'Sucursal '.$accion.'.');

        return true;
    }

    private function denegarSiNoEsPropia(Sucursal $sucursal): void
    {
        if ($sucursal->getUsuario() !== $this->getUser()) {
            throw $this->createNotFoundException('La sucursal no existe.');
        }
    }
}
