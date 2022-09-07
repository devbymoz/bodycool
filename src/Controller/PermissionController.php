<?php

namespace App\Controller;

use App\Entity\Permission;
use App\Form\AddPermissionType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

class PermissionController extends AbstractController
{
    /**
     * Permet de créer de nouvelles permissions
     *
     * @return Response
     */
    #[Route('/ajouter-permission', name: 'app_ajouter_permission')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $permission = new Permission();
        $repo = $doctrine->getRepository(Permission::class);

        $formAddPermission = $this->createForm(AddPermissionType::class, $permission);
        $formAddPermission->handleRequest($request);

        if($formAddPermission->isSubmitted() && $formAddPermission->isValid()) {
            $data = $formAddPermission->getData();
            $namePermission = $data->getName();

            $checkNamePermission = $repo->findBy(['name' => $namePermission]);
            
            // Vérifie que le nom n'existe pas déja en BDD
            if($checkNamePermission != []) {
                $this->addFlash(
                    'notice',
                    'Cette permission existe déjà'
                );
            } else {
                $em->persist($data);
                $em->flush();

                $this->addFlash(
                    'success',
                    'La permission à bien été créée'
                );

                return $this->redirectToRoute('app_ajouter_permission');
            }
        }

        return $this->render('permission/add-permission.html.twig', [
            'formAddPermission' => $formAddPermission->createView()
        ]);
    }


    /**
     * Récupère la liste de toutes les permissions de la BDD
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
