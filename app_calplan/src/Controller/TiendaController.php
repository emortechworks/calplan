<?php

namespace App\Controller;

use App\Entity\Tienda;
use App\Entity\User;
use App\Form\TiendaType;
use App\Repository\TiendaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tiendas')]
#[IsGranted('ROLE_USER')]
class TiendaController extends AbstractController
{
    public function __construct(private TiendaRepository $tiendaRepository)
    {
    }

    #[Route('', name: 'app_tienda_index')]
    public function index(): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();

        return $this->render('tienda/index.html.twig', [
            'tiendas' => $this->tiendaRepository->delUsuario($usuario),
        ]);
    }

    #[Route('/nueva', name: 'app_tienda_nueva', methods: ['GET', 'POST'])]
    public function nueva(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();
        $tienda = new Tienda();
        $form = $this->createForm(TiendaType::class, $tienda, [
            'usuario' => $usuario,
            'tienda' => $tienda,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tienda->setUsuario($usuario);
            if ($this->intentarGuardar($form, $tienda, null, $entityManager, 'creada')) {
                return $this->redirectToRoute('app_tienda_index');
            }
        }

        return $this->render('tienda/nueva.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_tienda_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Tienda $tienda, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropia($tienda);

        /** @var User $usuario */
        $usuario = $this->getUser();
        $form = $this->createForm(TiendaType::class, $tienda, [
            'usuario' => $usuario,
            'tienda' => $tienda,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->intentarGuardar($form, $tienda, $tienda->getId(), $entityManager, 'actualizada')) {
                return $this->redirectToRoute('app_tienda_index');
            }
        }

        return $this->render('tienda/editar.html.twig', [
            'form' => $form->createView(),
            'tienda' => $tienda,
        ]);
    }

    #[Route('/{id}/alternar', name: 'app_tienda_alternar', methods: ['POST'])]
    public function alternar(Request $request, Tienda $tienda, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropia($tienda);

        if (!$this->isCsrfTokenValid('alternar'.$tienda->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $tienda->setActiva(!$tienda->isActiva());
        $entityManager->flush();

        $this->addFlash('success', $tienda->isActiva() ? 'Tienda reactivada.' : 'Tienda desactivada.');

        return $this->redirectToRoute('app_tienda_index');
    }

    private function intentarGuardar(FormInterface $form, Tienda $tienda, ?int $excluirId, EntityManagerInterface $entityManager, string $accion): bool
    {
        $usuario = $tienda->getUsuario();

        if ($this->tiendaRepository->nombreYaExiste($usuario, (string) $tienda->getNombre(), $excluirId)) {
            $form->get('nombre')->addError(new FormError('Ya tienes una tienda con ese nombre.'));

            return false;
        }

        $codigo = $tienda->getCodigo();
        if ($codigo !== null && $codigo !== '') {
            if ($this->tiendaRepository->codigoYaExiste($usuario, $codigo, $excluirId)) {
                $form->get('codigo')->addError(new FormError('Ya tienes una tienda con ese código.'));

                return false;
            }
        } else {
            $tienda->setCodigo(null);
        }

        $entityManager->persist($tienda);
        $entityManager->flush();

        $this->addFlash('success', 'Tienda '.$accion.'.');

        return true;
    }

    private function denegarSiNoEsPropia(Tienda $tienda): void
    {
        if ($tienda->getUsuario() !== $this->getUser()) {
            throw $this->createNotFoundException('La tienda no existe.');
        }
    }
}
