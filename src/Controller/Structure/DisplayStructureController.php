<?php

namespace App\Controller\Structure;

use App\Entity\Franchise;
use App\Entity\Permission;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

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
    #[Route('/{numpage<\d+>}', options: ['expose' => true], name: 'app_list_structure', methods: ['GET'])]
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

        // Les paramètres GET de la recherche.
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

        // Nombre d'éléments à afficher par page.
        $nbPerPage = 9;

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
        foreach ($structureRepo->getNbrElement() as $value) {
            if ($value['active'] === true) {
                $nbrStructureEnable++;
            }
            if ($value['active'] === false) {
                $nbrStructureEnable++;
            }
        }

        // On appelle le service de pagination
        $paginationService->myPagination($numpage, $nbPerPage, $nbrStructure);

        // On récupère certaines valeurs pour les afficher dans la vue.
        $pagination = $paginationService->getPagination();
        $nbPage = $paginationService->getNbPage();

        // On redigire si le numéro de page est superieur au nombre de page disponible.
        if ($numpage > $nbPage && $numpage != 1) {
            return $this->redirectToRoute('app_list_structure');
        }

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
            return $this->json([
                'code' => 200,
                'content' => $this->renderView('include/_structure-listing.html.twig', [
                    'form' => $form->createView(),
                    'numpage' => $numpage,
                    'paramActive' => $paramActive,
                    'nbrAllElement' => $nbrStructure
                ]),
                'pagination' => $this->renderView('include/_pagination.html.twig', [
                    'pagination' => $pagination,
                    'paginationService' => $paginationService,
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
                'paginationService' => $paginationService,
                'paramActive' => $paramActive,
                'paramName' => $paramName,
                'paramId' => $paramId,
                'nbPage' => $nbPage,
                'numpage' => $numpage,
                'nbrAllElement' => $nbrStructure,
                'nbrElementEnable' => $nbrStructureEnable,
                'nbrElementDisable' => $nbrStructureDisable,
            ]);
        }
    }




    /**
     * AFFICHE LES DÉTAILS D'UNE STRUCTURE
     * 
     * En lecture seul pour le role Franchise et Gestionnaire.
     * En écriture pour le role Admin
     * 
     * - Le franchisé peut accéder à toutes les structures de sa franchise.
     * - Le gestionnaire peut accéder à la structure qu'il gere.
     * - Le technicien peut accéder à toutes les structures.
     * 
     * @return Response
     */
    #[Route('/{slug}/{id<\d+>}', name: 'app_structure_unique')]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function singleStucture(
        $id,
        $slug,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        $repo = $doctrine->getRepository(Structure::class);

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

        // On récupère l'id de l'utilisateur connecté.
        $idUserConnected = $this->getUser()->getId();

        // On vérifie si l'utilisateur connecté est un admin.
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Si l'utilisateur connecté n'est pas le gestionnaire de la structure, le franchisé de la structure ou un admin on interdit l'accès à la page.
        if (!$isAdmin && ($idUserConnected != $userOwner->getId()) && ($idUserConnected != $userAdminStructure->getId())) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette page');
        }

        // Si la structure est désactivé et que l'utilisateur connecté n'est pas un admin on interdit l'accès à la page.
        if ($structure->isActive() === false && !$isAdmin) {
            throw $this->createAccessDeniedException('Cette structure est désactivée');
        }

        // On récupère le formulaire des permissions et de l'état de la structure.
        $form = $this->createFormBuilder($structure)
            ->add('structurePermissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => 'id',
                'choice_value' => 'id',
                'multiple' => true,
                'expanded' => true,
                'mapped' => true
            ])
            ->add('active', CheckboxType::class, [
                'label'    => 'active',
                'required' => false,
                'mapped' => true,
            ])
            ->getForm();

        $form->handleRequest($request);

        // On récupère les id des permissions de la structure
        $idStructurePermissions = $form['structurePermissions']->getViewData();

        // On récupère tous les champs des permissions globales de la franchise.
        $arrGlobalPermissions = $franchise->getGlobalPermissions()->toArray();

        // Tableau pour récupérer uniquement les id des P globales.
        $idGlobalPermissions = [];
        foreach ($arrGlobalPermissions as $value) {
            array_push($idGlobalPermissions, $value->getId());
        }

        // On fusion les P globales et classique, et on supprime les doublons.
        $mixedPermissions = array_merge($idGlobalPermissions, $idStructurePermissions);
        $mixedPermissions = array_unique($mixedPermissions);

        return $this->renderForm('structure/single-structure.html.twig', [
            'structure' => $structure,
            'franchise' => $franchise,
            'userAdminStructure' => $userAdminStructure,
            'form' => $form,
            'mixedPermissions' => $mixedPermissions,
            'idGlobalPermissions' => $idGlobalPermissions
        ]);
    }
}
