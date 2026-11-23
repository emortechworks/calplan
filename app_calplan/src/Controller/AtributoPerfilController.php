<?php

namespace App\Controller;

use App\Entity\AtributoPerfil;
use App\Entity\User;
use App\Form\AtributoPerfilType;
use App\Repository\AtributoPerfilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/perfil/atributos')]
#[IsGranted('ROLE_USER')]
class AtributoPerfilController extends AbstractController
{
    public function __construct(private AtributoPerfilRepository $atributoRepository)
    {
    }

    #[Route('', name: 'app_atributo_perfil_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();
        $atributos = $this->atributoRepository->delUsuario($usuario);

        return $this->render('atributo_perfil/index.html.twig', [
            'atributos' => $atributos,
        ]);
    }

    #[Route('/nuevo', name: 'app_atributo_perfil_nuevo', methods: ['GET', 'POST'])]
    public function nuevo(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();
        $atributo = new AtributoPerfil();
        $atributo->setOrden(\count($this->atributoRepository->delUsuario($usuario)) + 1);

        $form = $this->createForm(AtributoPerfilType::class, $atributo, [
            'tipo_bloqueado' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $atributo->setUsuario($usuario);

            if ($this->intentarGuardar($form, $atributo, null, $entityManager, 'creado')) {
                return $this->redirectToRoute('app_atributo_perfil_index');
            }
        }

        return $this->render('atributo_perfil/nuevo.html.twig', [
            'form' => $form->createView(),
            'tipoBloqueado' => false,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_atributo_perfil_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, AtributoPerfil $atributo, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropio($atributo);

        $tipoOriginal = $atributo->getTipo();
        $tipoBloqueado = $this->atributoRepository->tieneValoresCapturados($atributo);

        $form = $this->createForm(AtributoPerfilType::class, $atributo, [
            'tipo_bloqueado' => $tipoBloqueado,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Impide alterar el tipo cuando ya existen valores capturados, incluso manipulando el POST.
            if ($tipoBloqueado) {
                $atributo->setTipo($tipoOriginal);
            }

            if ($form->isValid() && $this->intentarGuardar($form, $atributo, $atributo->getId(), $entityManager, 'actualizado')) {
                return $this->redirectToRoute('app_atributo_perfil_index');
            }
        }

        return $this->render('atributo_perfil/editar.html.twig', [
            'form' => $form->createView(),
            'atributo' => $atributo,
            'tipoBloqueado' => $tipoBloqueado,
        ]);
    }

    #[Route('/{id}/alternar', name: 'app_atributo_perfil_alternar', methods: ['POST'])]
    public function alternar(Request $request, AtributoPerfil $atributo, EntityManagerInterface $entityManager): Response
    {
        $this->denegarSiNoEsPropio($atributo);

        if (!$this->isCsrfTokenValid('alternar_atributo_perfil'.$atributo->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $atributo->setActivo(!$atributo->isActivo());
        $entityManager->flush();

        $this->addFlash('success', $atributo->isActivo() ? 'Atributo reactivado.' : 'Atributo desactivado.');

        return $this->redirectToRoute('app_atributo_perfil_index');
    }

    #[Route('/sugeridos', name: 'app_atributo_perfil_sugeridos', methods: ['POST'])]
    public function cargarSugeridos(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('cargar_sugeridos_perfil', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        /** @var User $usuario */
        $usuario = $this->getUser();
        $agregados = 0;

        foreach (AtributoPerfil::SUGERIDOS as $sugerido) {
            if ($this->atributoRepository->nombreYaExiste($usuario, $sugerido['nombre'])) {
                continue;
            }

            $atributo = new AtributoPerfil();
            $atributo->setUsuario($usuario)
                ->setNombre($sugerido['nombre'])
                ->setTipo($sugerido['tipo'])
                ->setOpciones($sugerido['opciones'])
                ->setOrden($sugerido['orden']);

            $entityManager->persist($atributo);
            ++$agregados;
        }

        if ($agregados > 0) {
            $entityManager->flush();
            $this->addFlash('success', \sprintf('Se agregaron %d atributos sugeridos.', $agregados));
        } else {
            $this->addFlash('info', 'Ya tienes todos los atributos sugeridos en tu catálogo.');
        }

        return $this->redirectToRoute('app_atributo_perfil_index');
    }

    private function intentarGuardar(
        FormInterface $form,
        AtributoPerfil $atributo,
        ?int $excluirId,
        EntityManagerInterface $entityManager,
        string $accion,
    ): bool {
        /** @var User $usuario */
        $usuario = $atributo->getUsuario();

        if ($this->atributoRepository->nombreYaExiste($usuario, (string) $atributo->getNombre(), $excluirId)) {
            $form->get('nombre')->addError(new FormError('Ya tienes un atributo de perfil con ese nombre.'));

            return false;
        }

        if ($atributo->getTipo() === AtributoPerfil::TIPO_LISTA) {
            $opciones = AtributoPerfil::normalizarOpciones((string) $form->get('opcionesTexto')->getData());
            if ($opciones === []) {
                $form->get('opcionesTexto')->addError(new FormError('Agrega al menos una opción para el tipo lista.'));

                return false;
            }

            $atributo->setOpciones($opciones);
        } else {
            $atributo->setOpciones(null);
        }

        $entityManager->persist($atributo);
        $entityManager->flush();

        $this->addFlash('success', 'Atributo de perfil '.$accion.'.');

        return true;
    }

    private function denegarSiNoEsPropio(AtributoPerfil $atributo): void
    {
        if ($atributo->getUsuario() !== $this->getUser()) {
            throw $this->createNotFoundException('El atributo de perfil no existe.');
        }
    }
}
