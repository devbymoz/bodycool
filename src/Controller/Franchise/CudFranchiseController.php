<?php 

namespace App\Controller\Franchise;

use App\Entity\Franchise;
use App\Entity\Permission;
use App\Entity\User;
use App\Form\Franchise\AddFranchiseType;
use App\Service\EmailService;
use App\Service\LoggerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


/**
 * CRÉATION, MISE À JOUR, SUPPRESSION DES FRANCHISES
 * 
 */
#[Route('/franchises')]
class CudFranchiseController extends AbstractController
{
    /**
     * CRÉATION D'UNE NOUVELLE FRANCHISE
     * - Créer une nouvelle franchise.
     * - Créer un nouvelle utilisateur qui sera le propriétaire de la franchise.
     * - Attribution des permissions globales qui seront liées à la franchise.
     * 
     * @return Response
     */
    #[Route('/ajouter-franchise', name: 'app_ajouter_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function addFranchise(
        Request $request, 
        ManagerRegistry $doctrine, 
        EmailService $emailService,
        LoggerService $loggerService
        ): Response
    {
        $em = $doctrine->getManager();
        
        $repoUser = $doctrine->getRepository(User::class);
        $repoFranchise = $doctrine->getRepository(Franchise::class);
        
        $formFranchise = $this->createForm(AddFranchiseType::class);
        $formFranchise->handleRequest($request);
            
        if($formFranchise->isSubmitted() && $formFranchise->isValid()) {
            $data = $formFranchise->getData();
            $user = $data->getUserOwner();
            
            // Vérifie si l'email existe déja en BDD.
            $email = $user->getEmail();
            $checkEmail = $repoUser->findOneBy(['email' => $email]);
            
            // Vérifie si le nom de la franchise existe déja en BDD.
            $nameFranchise = $data->getName();
            $checkNameFranchise = $repoFranchise->findOneBy(['name' => $nameFranchise]);
            
            if($checkEmail != []) {
                $this->addFlash(
                    'notice',
                    'Cet email existe déjà'
                );
            } elseif ($checkNameFranchise != []) {
                $this->addFlash(
                    'notice',
                    'Cette franchise existe déjà'
                );
            } else {
                // On attribue le role Franchise à l'utilisateur.
                $user->setRoles(['ROLE_FRANCHISE']);

                try {
                    $em->persist($data);
                    $em->flush();
                    
                    $this->addFlash(
                        'success',
                        'La franchise a bien été créée'
                    );
                } catch (\Exception $e) {
                    $loggerService->logGeneric($e, 'Erreur persistance des données');

                    $this->addFlash(
                        'exception',
                        'La franchise n\'a pas pu être enregistrée en BDD. Log n° : ' . $loggerService->getErrorNumber()
                    );
                }

                // On envoi un email au franchisé pour qu'il confirme son compte.
                try {
                    $emailService->sendEmail(
                        $email, 
                        'Confirmer votre compte Franchise BodyCool', 
                        [
                            'user' => $user,
                        ], 
                        'emails/confirm-franchise.html.twig'
                    );
                } catch (TransportExceptionInterface $e) {
                    $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');
                    
                    $this->addFlash(
                        'exception',
                        'L\'email n\'a pas pu être envoyé au propriétaire. Log n° : ' . $loggerService->getErrorNumber()
                    );
                }
            }
        }
        
        return $this->renderForm('franchise/add-franchise.html.twig', [
            'formFranchise' => $formFranchise
        ]);
    }




    /**
     * PERMET D'ACTIVER OU DÉSACTIVER UNE FRANCHISE
     * 
     * @return Response Json
     */
    #[Route('/changer-etat-{id<\d+>}', options: ['expose' => true] , name: 'app_changer_etat_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function changeStateFranchise(
        $id, 
        ManagerRegistry $doctrine, 
        EmailService $emailService,
        LoggerService $loggerService
        ): Response
	{
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Franchise::class);

        // On récupère la franchise correspondant à l'id en paramètre.
        $franchise = $repo->findOneBy(['id' => $id]);
        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        $emailUserOwner = $franchise->getUserOwner()->getEmail();

        // On récupère l'état de la franchise (activée ou désactivée).
        $stateFranchise = $franchise->isActive();

        // On inverse l'état de la franchise.
        $franchise->setActive(!$stateFranchise);

        // On sauvegarde le nouvel état dans une variable
        $newStateFranchise = $franchise->isActive();

        // On sauvegarder le nouvel état de la franchise en BDD
        try {
            $em->flush();
        } catch (\Exception $e) {
            $loggerService->logGeneric($e, 'Erreur persistance des données');

            return $this->json([
                'code' => 500, 
                'message' => 'Erreur de persistance des données',
                'franchiseName' => $franchise->getName(), 
                'errorNumber' => $loggerService->getErrorNumber()
            ], 500);
        }

        // On envoi un email au franchisé pour lui indiquer le nouvel état de sa franchise.
        try {
            $emailService->sendEmail(
                $emailUserOwner, 
                'Votre franchise a été modifiée', 
                ['franchise' => $franchise], 
                'emails/change-state-franchise.html.twig'
            );
        } catch (TransportExceptionInterface $e) {
            $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');

            return $this->json([
                'code' => 500, 
                'message' => 'Erreur de distribution du mail.',
                'idFranchise' => $franchise->getId(),
                'errorNumber' => $loggerService->getErrorNumber(),
            ], 500);
        }


        return $this->json([
            'code' => 200, 
            'message' => 'franchise modifiée avec success',
            'newStateFranchise' => $newStateFranchise,
            'franchiseName' => $franchise->getName()
        ], 200);
    }



    /**
     * PERMET D'ACTIVER OU DÉSACTIVER LES PERMISSIONS GLOBALES
     * 
     * @return Response Json
     */
    #[Route('/changer-permission-globale-{id<\d+>}-{idGP<\d+>}', name: 'app_changer_permission-globale')]
    #[IsGranted('ROLE_ADMIN')]
    public function changeGlobalPermissionFranchise(
        $id, $idGP,
        ManagerRegistry $doctrine, 
        EmailService $emailService,
        LoggerService $loggerService
        )
	{
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Franchise::class);

        // On récupère la franchise correspondant à l'id en paramètre et on vérifie qu'elle existe
        $franchise = $repo->findOneBy(['id' => $id]);
        if(empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        // On vérifie que le paramètre idGP est bien une permissions
        $repoPermissions = $doctrine->getRepository(Permission::class);
        $permissions = $repoPermissions->findAll();

        $arrayExistPermissions = [];
        foreach ($permissions as $value) {
            array_push($arrayExistPermissions, $value->getId());
        }
        
        if (!in_array($idGP, $arrayExistPermissions)) {
            throw $this->createNotFoundException('Cette permission n\'existe pas.');
        }
        
        // On récupère l'objet permission correspondant à l'id passé en paramètre.
        $globalPermissionAtChange= [];
        foreach ($permissions as $value) {
            if($idGP == $value->getId()) {
                $globalPermissionAtChange= $value;
            }
        }
        $nameGlobalPermissionIdGP = $globalPermissionAtChange->getName();

        // On récupère un tableau des permissions globales qu'a la franchise.
        $GlobalPermissions = $franchise->getGlobalPermissions()->toArray();
        
        $arrayGlobalPermissionFranchise = [];
        foreach ($GlobalPermissions as $value) {
            array_push($arrayGlobalPermissionFranchise, $value->getId());
        }
        
         // Permet de voir quelle est la permission globale qui a été modifiée.
         $whatChange = null;

        // Si la permission est présente dans la liste des permissions globales de la franchise, alors on la supprime, sinon on l'ajoute.
        if(in_array($idGP, $arrayGlobalPermissionFranchise)) {
            $franchise->removeGlobalPermission($globalPermissionAtChange);
            $whatChange = "Suppression de la permission globale : $nameGlobalPermissionIdGP";
        } else {
            $franchise->addGlobalPermission($globalPermissionAtChange);
            $whatChange = "Ajout de la permission globale : $nameGlobalPermissionIdGP";
        }
        
        // On persist les nouvelles données de la franchise
        try {
            $em->flush();
        } catch (\Exception $e) {
            $loggerService->logGeneric($e, 'Erreur persistance des données');

            return $this->json([
                'code' => 500, 
                'message' => 'Erreur de persistance des données',
                'franchiseName' => $franchise->getName(), 
                'errorNumber' => $loggerService->getErrorNumber()
            ], 500);
        }

        // On envoi un email au franchisé pour lui indiquer quelle permission globale a été modifée.
        try {
            $emailService->sendEmail(
                $franchise->getUserOwner()->getEmail(), 
                'Une permission globale a été modifiée', 
                [
                    'userOwner' => $franchise->getUserOwner(),
                    'changeFranchise' => $whatChange
                ], 
                'emails/edit-franchise.html.twig'
            );
        } catch (TransportExceptionInterface $e) {
            $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');

            return $this->json([
                'code' => 500, 
                'message' => 'Erreur de distribution du mail.',
                'idFranchise' => $franchise->getId(),
                'errorNumber' => $loggerService->getErrorNumber(),
            ], 500);
        }

        return $this->json([
            'code' => 200, 
            'message' => 'Permission globale modifiée avec success',
            'whatChange' => $whatChange,
            'userOwner' => $franchise->getUserOwner()
        ], 200);
    }


}