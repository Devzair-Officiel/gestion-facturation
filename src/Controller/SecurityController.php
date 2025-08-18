<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin'); // déjà connecté ? va à l’admin
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),          // récupère la dernière saisie
            'error'         => $authUtils->getLastAuthenticationError(), // éventuelle erreur
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré par Symfony (config `logout` dans security.yaml)
        throw new \LogicException('Logout is handled by the firewall.');
    }
}
