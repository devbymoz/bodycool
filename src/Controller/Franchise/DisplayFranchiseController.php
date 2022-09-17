<?php 

namespace App\Controller\Franchise;

use App\Entity\Franchise;
use App\Entity\Permission;
use App\Form\Franchise\ActiveFranchiseType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;


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
    #[Route('/', name: 'app_list_franchise')]
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
    } 


    

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