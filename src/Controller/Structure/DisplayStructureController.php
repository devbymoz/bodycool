<?php

namespace App\Controller\Structure;

use App\Entity\Franchise;
use App\Entity\Structure;
use App\Form\Structure\ActiveStructureType;
use App\Repository\StructureRepository;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * AFFICHAGE DES STRUCTURES
 * 
 */
#[Route('/structures')]
class DisplayStructureController extends AbstractController
{


    /**
     * LISTE DE TOUTES LES STRUCTURES
     * 
     * @return Response
     */
    #[Route('/{numpage<\d+>}', name: 'app_list_structure', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function structureListing(
        Request $request,
        PaginationService $paginationService,
        StructureRepository $structureRepo,
        int $numpage = 1,
    ): Response {
        // On redirige si le numéro de page vaut 0
        if ($numpage === 0) {
            return $this->redirectToRoute($request->get('_route'));
        }

        // Les paramètres GET de la rechercher.
        $paramActive = $request->get('active');
        $paramId = $request->get('id');
        $paramName = $request->get('name');

        // On vérifie que le param active n'est pas different de 1 ou 0.
        if ($paramActive != 1 && $paramActive != 0 && empty($paramActive)) {
            return $this->redirectToRoute('app_list_structure');
        }

        // On vérifie que la valeur de l'id est bien un numerique.
        if (isset($paramId) && !is_numeric($paramId)) {
            return $this->redirectToRoute('app_list_structure');
        }

        // On récupère le nombre total de structure dans la BDD.
        $totalStructure = count($structureRepo->findAll());

        // Nombre d'éléments à afficher par page.
        $nbPerPage = 4;

        // On récupère les structures en fonction des paramètres de la requete.
        $structures = $structureRepo->findElementFilter(
            $paramActive,
            $paramId,
            $paramName,
            $nbPerPage,
            $numpage
        );

        // On récupère le nombre de structure avant la limitation SQL.
        $nbrStructure = count($structureRepo->getNbrElement());
        $nbrStructureEnable = 0;
        $nbrStructureDisable = 0;

        // On compte le nombre de structure activée et désactivée pour les envoyer à la vue.
        foreach ($structureRepo->getNbrElement() as $active) {
            if ($active->isActive() === true) {
                $nbrStructureEnable++;
            }
            if ($active->isActive() === false) {
                $nbrStructureDisable++;
            }
        }

        // On appelle le service de pagination
        $paginationService->myPagination($numpage, $nbPerPage, $nbrStructure);

        // On récupère certaines valeurs pour les afficher dans la vue.
        $pagination = $paginationService->getPagination();
        $nbPage = $paginationService->getNbPage();

        // On redigire si le numéro de page est superieur au nombre de page disponible.
        /* if($numpage > $nbPage && is_numeric($numpage)) {
            return $this->redirectToRoute('app_list_franchise');
        } */

        // On assigne le tableau de franchises dans la clé list.
        $data = ['list' => $structures];

        // Récupère le formulaire des checkbox pour activer ou désactiver une structure. 
        $form = $this->createFormBuilder($data)
            ->add('list', CollectionType::class, [
                'entry_type' => ActiveStructureType::class,
            ])
            ->getForm();

        // Si la requête reçu contient un param Ajax. 
        if ($request->get('ajax')) {
            return new JsonResponse([
                'code' => 200,
                'content' => $this->renderView('include/_structure-listing.html.twig', [
                    'form' => $form->createView(),
                    'numpage' => $numpage,
                    'paramActive' => $paramActive,
                    'nbrAllElement' => $nbrStructure
                ]),
                'pagination' => $this->renderView('include/_pagination.html.twig', [
                    'pagination' => $pagination,
                    'numpage' => $numpage,
                    'nbPage' => $nbPage,
                    'paramActive' => $paramActive,
                    'paramName' => $paramName,
                    'paramId' => $paramId,
                ]),
                'filterState' => $this->renderView('include/_filter-state.html.twig', [
                    'paramActive' => $paramActive,
                    'paramName' => $paramName,
                    'paramId' => $paramId,
                    'nbrAllElement' => $nbrStructure,
                    'nbrElementEnable' => $nbrStructureEnable,
                    'nbrElementDisable' => $nbrStructureDisable,
                ]),
                'nbrAllElement' => $nbrStructure,
            ], 200);
        } else {
            return $this->renderForm('structure/structure-listing.html.twig', [
                'form' => $form,
                'pagination' => $pagination,
                'paramActive' => $paramActive,
                'paramName' => $paramName,
                'paramId' => $paramId,
                'nbPage' => $nbPage,
                'numpage' => $numpage,
                'nbrAllElement' => $nbrStructure,
                'nbrElementEnable' => $nbrStructureEnable,
                'nbrElementDisable' => $nbrStructureDisable,
                'totalStructure' => $totalStructure
            ]);
        }
    }




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
