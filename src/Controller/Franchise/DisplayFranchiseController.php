<?php 

namespace App\Controller\Franchise;

use App\Entity\Franchise;
use App\Entity\Permission;
use App\Form\Franchise\ActiveFranchiseType;
use App\Service\PaginationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\JsonResponse;


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
    #[Route('/{page}-{numpage<\d+>}', name: 'app_list_franchise', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function franchiseListing(
        ManagerRegistry $doctrine,
        Request $request, 
        PaginationService $paginationService,
        string $page = 'page',
        int $numpage = 1,
        ): Response
    {
        $repo = $doctrine->getRepository(Franchise::class);
        $allFranchise = $repo->findAll();
        $totalAllFranchise = count($allFranchise);

        // Nombre d'éléments par page
        $nbPerPage = 4;

        // On redirige si le numéro de page est égal à 0
        if($numpage === 0) {
            return $this->redirectToRoute($request->get('_route'));
        }
        
        // Les critère que va contenir la requete SQL, suivant les paramètres récupérés.
        $criteriaRequest = [];

        // Traitement de l'état des franchise.
        $stateActive = $request->query->get('active');

        // On vérifie que le paramètre active est un 1 ou 0.
        if($stateActive != 1 && $stateActive != 0 && empty($stateActive)) {
            return $this->redirectToRoute('app_list_franchise');
        }

        // On attribut la valeur du parametre active au tableau de critère.
        if($stateActive === null) {
            $stateActive = null;
        } elseif($stateActive == 1) {
            $criteriaRequest = ['active' => $stateActive];
        } elseif($stateActive == 0) {
            $criteriaRequest = ['active' => $stateActive];
        }
        
        // On récupère toutes les éléments et on met une limite
        $franchises = $repo->findBy(
            $criteriaRequest,
            ['id' => 'ASC'], 
            $nbPerPage,
            $nbPerPage * ($numpage - 1)
        );

        // On appelle le service de pagination
        $paginationService->myPagination($numpage, $nbPerPage, $repo, $criteriaRequest);
        $nbPage = $paginationService->getNbPage();
        $pagination = $paginationService->getPagination();
        $nbrFranchise = $paginationService->getNbrElement();
        
        // On redigire si le numéro de page est supperieur au nombre de page disponible.
        if($numpage > $nbPage ) {
            return $this->redirectToRoute('app_list_franchise', ['numpage' => $nbPage]);
        }

        // On assigne le tableau de franchises dans la clé list
        $data = ['list' => $franchises];

        // Récupère le formulaire des checkbox pour activer ou désactiver une franchise. 
        $form = $this->createFormBuilder($data)
            ->add('list', CollectionType::class,[
                'entry_type' => ActiveFranchiseType::class,
                ])
            ->getForm();
   
        //dump($franchises[0]);
        //dd($data);
        $testFranchise = $repo->findAll();
        if ($request->isXmlHttpRequest()){
            return $this->json([
                'code' => 200, 
                'content' => json_encode($testFranchise),
            ], 200);
        } else {
            return $this->renderForm('franchise/franchise-listing.html.twig', [
                'form' => $form,
                'numpage' => $numpage,                         
                'nbPage' => $nbPage,
                'stateActive' => $stateActive,
                'nbrFranchise' => $nbrFranchise,
                'totalAllFranchise' => $totalAllFranchise,
                'pagination' => $pagination,
            ]);
        }
    } 






    ////Paggination

/* $nbPerPage = 1;

        // On redirige si le numéro de page est égal à 0
        if($numpage === 0) {
            return $this->redirectToRoute($request->get('_route'));
        }

        // On récupère toutes les franchises et on met une limite
        $franchises = $repo->findBy(
            [],
            ['id' => 'ASC'], 
            $nbPerPage,
            $nbPerPage * ($numpage - 1)
          );
        

        // On récupère toutes les franchises.
        $allFranchises = $repo->findAll();

        // On calcule le nombre qu'aura la page.
        $nbPage = intval(ceil(count($allFranchises) / $nbPerPage));

        // On redigire si le numéro de page est supperieur au nombre de page disponible.
        if($numpage > $nbPage ) {
            return $this->redirectToRoute('app_list_franchise', ['numpage' => $nbPage]);
        }
              
        // On crée un tableau avec le nombre de page total : les valeurs correspondent au numéro de la page.
        $paggination = range($numpage, $nbPage);
        
        // Si le nombre de page est supérieur à 3, on limite coupe le tableau pour afficher les 3 premières pages et la dernière.
        if($nbPage > 3) {
            array_splice($paggination, 3, -1);
        }
        // Si on coupe à 3, tu m'affiche des petits points apres le 3eme éléments
        

        // Si le numéro de page correspond à une des 3 dernières pages, on affiche la paggination des 3 dernières pages. 
        if($numpage > ($nbPage - 4)) {
            $paggination = range(3, $nbPage);
        } */























/*     #[Route('/', name: 'app_list_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function franchiseListing(ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Franchise::class);
        $franchises = $repo->findAll();

        // On assigne le tableau de franchises dans la clé list
        $data = ['list' => $franchises];

        // Récupère le formulaire des checkbox pour activer ou désactiver une franchise. 
        $form = $this->createFormBuilder($data)
            ->add('list', CollectionType::class,[
                'entry_type' => ActiveFranchiseType::class,
                ])
            ->getForm();
   
        return $this->renderForm('franchise/franchise-listing.html.twig', [
            'form' => $form,                                 
        ]);
    }  */


    








   /**
     * AFFICHE LES DÉTAILS D'UNE FRANCHISE
     * 
     * En lecture seul pour le role Franchise.
     * En écriture pour le role Admin
     * 
     * @return Response
     */
    #[Route('/franchise-{id<\d+>}', name: 'app_franchise_unique')]
    #[IsGranted('ROLE_FRANCHISE')]
    public function singleFranchise(
        $id,
        Request $request, 
        ManagerRegistry $doctrine, 
        ): Response
    {
        $repo = $doctrine->getRepository(Franchise::class);

        // On récupère la franchise correspondant à l'id passé en paramètre, on redirige vers une 404 si aucune franchise ne correspond à l'id.
        $franchise = $repo->findOneBy(['id' => $id]);
        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        // On récupère le propriétaire de la franchise.
        $userOwner = $franchise->getUserOwner();

        // On récupère l'utilisateur connecté.
        $userConnected = $this->getUser();

        // On vérifie si l'utilisateur connecté est un admin.
        $isAdmin = in_array('ROLE_ADMIN', $userConnected->getRoles());

        // Si l'utilisateur connecté n'est pas le propriétaire de la franchise et n'est pas un admin on interdit l'accès à la page.
        if ($userConnected != $userOwner && !$isAdmin) {
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
                'choice_value'=> 'id',
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
        
        // Récupère un tableau des id des permissions dont la franchise déjà accès. 
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





}