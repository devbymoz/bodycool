<?php

namespace App\Controller\Permission;

use App\Entity\Permission;
use App\Form\Permission\AddPermissionType;
use App\Service\LoggerService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;


/**
 * CRÉATION, LECTURE, MISE À JOUR ET SUPPRESSION DE PERMISSION
 * 
 */
class CrudPermissionController extends AbstractController
{

    
    /**
     * CRÉATION D'UNE PERMISSION
     *
     * @return Response
     */
    #[Route('/ajouter-permission', name: 'app_ajouter_permission')]
    #[IsGranted('ROLE_ADMIN')]
    public function addPermission(
        Request $request, 
        ManagerRegistry $doctrine, 
        LoggerService $loggerService
        ): Response
    {
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Permission::class);
        $permission = new Permission();

        $formAddPermission = $this->createForm(AddPermissionType::class, $permission);
        $formAddPermission->handleRequest($request);

        if($formAddPermission->isSubmitted() && $formAddPermission->isValid()) {
            $data = $formAddPermission->getData();
            $namePermission = $data->getName();

            // On vérifie que la permission n'existe pas déjà en BDD
            $checkNamePermission = $repo->findBy(['name' => $namePermission]);
            if($checkNamePermission != []) {
                $this->addFlash(
                    'notice',
                    'Cette permission existe déjà'
                );
            } else {
                try {
                    $em->persist($data);
                    $em->flush();
    
                    $this->addFlash(
                        'success',
                        'La permission à bien été créée'
                    );
                    return $this->redirectToRoute('app_ajouter_permission');
                } catch (\Exception $e) {
                    $loggerService->logGeneric($e, 'Erreur fichier télécharger');

                    $this->addFlash(
                        'exception',
                        'La permission n\'a pas pu être enregistréé en BDD. Log n° : ' . $loggerService->getErrorNumber()
                    );
                }
            }
        }

        return $this->render('permission/add-permission.html.twig', [
            'formAddPermission' => $formAddPermission->createView()
        ]);
    }



    
    /**
     * AFFICHE TOUTES LES PERMISSIONS
     *
     * @return Response
     */
    #[Route('/liste-permissions', name: 'app_liste_permissions')]
    #[IsGranted('ROLE_ADMIN')]
    public function listPermissions(ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Permission::class);

        $permissions = $repo->findAll();
        $nbPermission = count($permissions);

        return $this->render('permission/list-permissions.html.twig', [
            'permissions' => $permissions,
            'nbPermission' => $nbPermission
        ]);
    }





}
