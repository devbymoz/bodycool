<?php

namespace App\Controller\Structure;

use App\Entity\Structure;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AFFICHAGE DES STRUCTURES
 * 
 */
#[Route('/structures')]
class DisplayStructureController extends AbstractController
{
    /**
     * LISTE DES STRUCTURES APPARTENANT À UNE FRANCHISE.
     * 
     * @return Response
     */
    #[Route('/mes-structures', name: 'app_mes_structures')]
    #[IsGranted('ROLE_FRANCHISE')]
    public function myStructures(        
        ManagerRegistry $doctrine,
       
    ): Response {
        // On récupère l'utisateur connecté et l'id de sa franchise.
        $user = $this->getUser();
        $idFranchise = $user->getFranchise()->getID();

        $repo = $doctrine->getRepository(Structure::class);

        // On récupère toutes les structures appertenant à la franchise.
        $myStructures = $repo->findBy(['franchise' => $idFranchise]);

        // On récupère le nombre de structure désactivée.
        $nbrDtructureDisable = count($repo->findBy(['active' => false]));

        return $this->render('structure/list-my-structures.html.twig', [
            'mesStructures' => $myStructures,
            'user' => $user,
            'nbrDtructureDisable' => $nbrDtructureDisable
        ]);
    }


}