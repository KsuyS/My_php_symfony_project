<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SecurityController extends AbstractController
{
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('login.html.twig', [
            'error' => $error
        ]);
    }

    public function logout(SessionInterface $session): Response
    {
        $session->invalidate();
        return $this->redirectToRoute('index');
    }
}