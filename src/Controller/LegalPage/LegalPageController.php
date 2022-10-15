<?php

namespace App\Controller\LegalPage;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegalPageController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function legalNotice(): Response
    {
        return $this->render('others/legal-notice.html.twig', [
            'controller_name' => 'LegalPageController',
        ]);
    }

    #[Route('/politique-confidentialite', name: 'app_politique_confidentialite')]
    public function privacyPolicy(): Response
    {
        return $this->render('others/privacy-policy.html.twig', [
            'controller_name' => 'LegalPageController',
        ]);
    }


}
