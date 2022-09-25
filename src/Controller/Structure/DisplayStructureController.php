<?php

namespace App\Controller\Structure;

use App\Entity\Franchise;
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




    /**
     * AFFICHE LES DÉTAILS D'UNE STRUCTURE
     * 
     * En lecture seul pour le role Franchise et Gestionnaire.
     * En écriture pour le role Admin
     * 
     * @return Response
     */
    #[Route('/structure-{id<\d+>}', name: 'app_structure_unique')]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function singleStucture(
        $id,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        // Le franchisé peut accéder à toutes les structures de sa franchise.
        // Le gestionnaire peut accéder à la structure qu'il gere.
        // le technicien peut accéder à toutes les structures

        $repo = $doctrine->getRepository(Structure::class);
        $repoFranchise = $doctrine->getRepository(Franchise::class);

        $structure = $repo->findOneBy(['id' => $id]);
        if (empty($structure)) {
            throw $this->createNotFoundException('Cette structure n\'existe pas.');
        }

        // On récupère la franchise à qui appartient la structure.
        $franchise = $structure->getFranchise();

        // On récupère le gestionnaire de la structure.
        $userAdminStructure = $structure->getUserAdmin();

        // On récupère le propriétaire de la franchise.
        $userOwner = $structure->getFranchise()->getUserOwner();

        // On récupère l'utilisateur connecté.
        $userConnected = $this->getUser();

        // On vérifie si l'utilisateur connecté est un admin.
        $isAdmin = in_array('ROLE_ADMIN', $userConnected->getRoles());

        // Si l'utilisateur connecté n'est pas le gestionnaire de la structure, le franchisé de la structure et un admin on interdit l'accès à la page.
        if ($userConnected != $userOwner && $userConnected != $userAdminStructure && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette page');
        }

        // Si la structure est désactivé et que l'utilisateur connecté n'est pas un admin on interdit l'accès à la page.
        if ($structure->isActive() === false && !$isAdmin) {
            throw $this->createAccessDeniedException('Cette structure est désactivée');
        }




        return $this->renderForm('structure/single-structure.html.twig', [
            'structure' => $structure,
            'franchise' => $franchise,
            'userAdminStructure' => $userAdminStructure



        ]);
    }





}
