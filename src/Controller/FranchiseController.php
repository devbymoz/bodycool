<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class FranchiseController extends AbstractController
{

    #[Route('/ajouter-franchise', name: 'app_ajouter.franchise')]
    public function addFranchise(Request $request, ManagerRegistry $doctrine): Response
    {
        
        $em = $doctrine->getManager();
        $user = new User();
        $repo = $doctrine->getRepository(User::class);

        $formUser = $this->createForm(UserType::class, $user);
        $formUser->handleRequest($request);

        if($formUser->isSubmitted() && $formUser->isValid()) {
            $data = $formUser->getData();
            $email = $data->getEmail();
            
            // Vérifie si un email existe déja en BDD
            $checkEmail = $repo->findBy(['email' => $email]);

            if($checkEmail != []) {
                $this->addFlash(
                    'notice',
                    'Cet email existe déjà'
                );
            } else {
                // Initialisation des propriétés pour un User Franchise
                $user->setRoles(['ROLE_FRANCHISE']);
                $user->setActive(0);
                $user->setCreateAt(new DateTimeImmutable('now'));
                $em->persist($data);
                $em->flush();

                $this->addFlash(
                    'success',
                    'La franchise a bien été créée'
                );  
            }
        }
        
        return $this->renderForm('franchise/ajouter-franchise.html.twig', [
            'formUser' => $formUser,
        ]);
    }
}
