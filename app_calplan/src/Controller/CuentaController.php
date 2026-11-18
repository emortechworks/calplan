<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CuentaController extends AbstractController
{
    // Requiere sesión de usuario; si no hay sesión, redirige a login.
    #[Route('/cuenta', name: 'app_cuenta')]
    public function index(): Response
    {
        return $this->render('cuenta/index.html.twig');
    }
}
