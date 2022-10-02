<?php

namespace App\Controller\Franchise;

use App\Entity\Franchise;
use App\Entity\Permission;
use App\Entity\Structure;
use App\Service\PaginationService;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\Franchise\ActiveFranchiseType;
use App\Repository\FranchiseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * AFFICHAGE DES FRANCHISES
 * 
 */
#[Route('/franchises')]
class DisplayFranchiseController extends AbstractController
{



    /**
     * LISTE DE TOUTES LES FRANCHISES
     * 
     * @return Response
     */
    #[Route('/{numpage<\d+>}', options: ['expose' => true], name: 'app_list_franchise', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function franchiseListing(
        Request $request,
        PaginationService $paginationService,
        FranchiseRepository $franchiseRepo,
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
            return $this->redirectToRoute('app_list_franchise');
        }

        // On vérifie que la valeur de l'id est bien un numerique.
        if (isset($paramId) && !is_numeric($paramId)) {
            return $this->redirectToRoute('app_list_franchise');
        }

        // On récupère le nombre total de franchise dans la BDD.
        $totalFranchise = count($franchiseRepo->findAll());

        // Nombre d'éléments à afficher par page.
        $nbPerPage = 9;

        // On récupère les franchises en fonction des paramètres de la requete.
        $franchises = $franchiseRepo->findFranchisesFilter(
            $paramActive,
            $paramId,
            $paramName,
            $nbPerPage,
            $numpage
        );

        // On récupère le nombre de franchise avant la limitation SQL.
        $nbrFranchise = count($franchiseRepo->getNbrElement());
        $nbrFranchiseEnable = 0;
        $nbrFranchiseDisable = 0;

        // On compte le nombre de franchise activée et désactivée pour les envoyer à la vue.
        foreach ($franchiseRepo->getNbrElement() as $active) {
            if ($active->isActive() === true) {
                $nbrFranchiseEnable++;
            }
            if ($active->isActive() === false) {
                $nbrFranchiseDisable++;
            }
        }

        // On appelle le service de pagination
        $paginationService->myPagination($numpage, $nbPerPage, $nbrFranchise);

        // On récupère certaines valeurs pour les afficher dans la vue.
        $pagination = $paginationService->getPagination();
        $nbPage = $paginationService->getNbPage();

        // On redigire si le numéro de page est superieur au nombre de page disponible.
        if ($numpage > $nbPage && $numpage != 1) {
            return $this->redirectToRoute('app_list_franchise');
        }

        // On assigne le tableau de franchises dans la clé list.
        $data = ['list' => $franchises];

        // Récupère le formulaire des checkbox pour activer ou désactiver une franchise. 
        $form = $this->createFormBuilder($data)
            ->add('list', CollectionType::class, [
                'entry_type' => ActiveFranchiseType::class,
            ])
            ->getForm();

        // Si la requête reçu contient un param Ajax. 
        if ($request->get('ajax')) {
            return $this->json([
                'code' => 200,
                'content' => $this->renderView('include/_franchise-listing.html.twig', [
                    'form' => $form->createView(),
                    'numpage' => $numpage,
                    'paramActive' => $paramActive,
                    'nbrAllElement' => $nbrFranchise
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
                    'nbrAllElement' => $nbrFranchise,
                    'nbrElementEnable' => $nbrFranchiseEnable,
                    'nbrElementDisable' => $nbrFranchiseDisable,
                ]),
                'nbrAllElement' => $nbrFranchise,
            ], 200);
        } else {
            return $this->renderForm('franchise/franchise-listing.html.twig', [
                'form' => $form,
                'pagination' => $pagination,
                'paginationService' => $paginationService,
                'paramActive' => $paramActive,
                'paramName' => $paramName,
                'paramId' => $paramId,
                'nbPage' => $nbPage,
                'numpage' => $numpage,
                'nbrAllElement' => $nbrFranchise,
                'nbrElementEnable' => $nbrFranchiseEnable,
                'nbrElementDisable' => $nbrFranchiseDisable,
                'totalFranchise' => $totalFranchise,
            ]);
        }
    }




    /**
     * AFFICHE LES DÉTAILS D'UNE FRANCHISE
     * 
     * En lecture seul pour le role Franchise.
     * En écriture pour le role Admin
     * 
     * @return Response
     */
    #[Route('/{slug}/{id<\d+>}', name: 'app_franchise_unique')]
    #[IsGranted('ROLE_FRANCHISE')]
    public function singleFranchise(
        $id,
        $slug,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        $repo = $doctrine->getRepository(Franchise::class);

        // On récupère la franchise correspondant à l'id passé en paramètre, on redirige vers une 404 si aucune franchise ne correspond à l'id.
        $franchise = $repo->findOneBy(['id' => $id]);
        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        // On récupère le propriétaire de la franchise.
        $userOwner = $franchise->getUserOwner();

        // On récupère l'id de l'utilisateur connecté.
        $idUserConnected = $this->getUser()->getId();

        // On vérifie si l'utilisateur connecté est un admin.
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Si l'utilisateur connecté n'est pas le propriétaire de la franchise et n'est pas un admin on interdit l'accès à la page.
        if ($idUserConnected != $userOwner->getId() && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette page');
        }

        // Si la franchise est désactivé et que l'utilisateur connecté n'est pas un admin on interdit l'accès à la page.
        if ($franchise->isActive() === false && !$isAdmin) {
            throw $this->createAccessDeniedException('Cette franchise est désactivée');
        }

        // On met toutes les permissions globales de la franchise dans un tableau.
        $allGlobalPermissionsFranchise = $franchise->getGlobalPermissions()->toArray();

        // On récupère le formulaire des permissions globales et de l'état de la franchise.
        $form = $this->createFormBuilder($franchise)
            ->add('globalPermissions', EntityType::class, [
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

        // Récupère un tableau des id des permissions dont la franchise a déjà accès. 
        $idGlobalPermissions = $form['globalPermissions']->getViewData();

        // On crée un tableau qui va contenir uniquement les noms des permissions déjà acquis.
        $valueGlobalPermissionsFranchise = [];
        foreach ($allGlobalPermissionsFranchise as $value) {
            array_push($valueGlobalPermissionsFranchise, $value->getName());
        }

        return $this->renderForm('franchise/single-franchise.html.twig', [
            'franchise' => $franchise,
            'form' => $form,
            'idGlobalPermissions' => $idGlobalPermissions,
        ]);
    }




    /**
     * LISTE DES STRUCTURES APPARTENANT À UNE FRANCHISE.
     * 
     * @return Response
     */
    #[Route('/{slug}/{id<\d+>}/structures', name: 'app_mes_structures')]
    #[IsGranted('ROLE_FRANCHISE')]
    public function myStructures(
        ManagerRegistry $doctrine,
        $id, // id franchise
        $slug,
    ): Response {
        // On récupère la franchise correspond à l'id.
        $repoFranchise = $doctrine->getRepository(Franchise::class);
        $franchise = $repoFranchise->findOneBy(['id' => $id]);

        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        // On récupère les structures appartenant à la franchise demandée.
        $repoStructure = $doctrine->getRepository(Structure::class);
        $structures = $repoStructure->findBy(['franchise' => $id]);

        // On récupère le propriétaire de la franchise.
        $userOwner = $franchise->getUserOwner();

        // On récupère l'utisateur connecté.
        $user = $this->getUser();

        // On vérifie si l'utilisateur connecté est un admin.
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Si l'utilisateur connecté n'est pas le propriétaire de la franchise et n'est pas un admin on interdit l'accès à la page.
        if ($user->getId() != $userOwner->getId() && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette page');
        }

        return $this->render('structure/list-my-structures.html.twig', [
            'structures' => $structures,
            'userOwner' => $userOwner,
        ]);
    }
}
