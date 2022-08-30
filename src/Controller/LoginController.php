<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/connexion', name: 'app_connexion')]
    public function login(): Response
    {
        return $this->render('login/connexion.html.twig');
    }

    #[Route('/mot-de-passe-perdu', name: 'app_mot_de_passe_perdu')]
    public function resendPassword(): Response
    {
        return $this->render('login/index.html.twig');
    }

    #[Route('/profil', name: 'app_profil')]
    public function profil(): Response
    {
        return $this->render('login/profil.html.twig');
    }

    

}
