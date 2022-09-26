<?php

namespace App\Controller\Franchise;

use App\Entity\Franchise;
use App\Entity\Permission;
use App\Entity\User;
use App\Form\Franchise\AddFranchiseType;
use App\Repository\FranchiseRepository;
use App\Service\ChangeStateService;
use App\Service\EmailService;
use App\Service\LoggerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


/**
 * CRÉATION, MISE À JOUR, SUPRESSION DES FRANCHISES
 * 
 */
#[Route('/franchises')]
class CudFranchiseController extends AbstractController
{


    /**
     * CRÉATION D'UNE NOUVELLE FRANCHISE
     * - Crée une nouvelle franchise.
     * - Crée un nouvel utilisateur qui sera le propriétaire de la franchise.
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
    ): Response {
        $em = $doctrine->getManager();

        $repoUser = $doctrine->getRepository(User::class);
        $repoFranchise = $doctrine->getRepository(Franchise::class);

        $formFranchise = $this->createForm(AddFranchiseType::class);
        $formFranchise->handleRequest($request);

        if ($formFranchise->isSubmitted() && $formFranchise->isValid()) {
            $data = $formFranchise->getData();
            $user = $data->getUserOwner();

            // Vérifie si l'email existe déja en BDD.
            $email = $user->getEmail();
            $checkEmail = $repoUser->findOneBy(['email' => $email]);

            // Vérifie si le nom de la franchise existe déja en BDD.
            $nameFranchise = $data->getName();
            $checkNameFranchise = $repoFranchise->findOneBy(['name' => $nameFranchise]);

            if ($checkEmail != []) {
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
    #[Route('/changer-etat-{id<\d+>}', name: 'app_changer_etat_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function changeStateFranchise(
        $id,
        ManagerRegistry $doctrine,
        EmailService $emailService,
        LoggerService $loggerService,
        ChangeStateService $changeStateService
    ): Response {
        $repo = $doctrine->getRepository(Franchise::class);

        // On récupère la franchise correspondant à l'id en paramètre.
        $franchise = $repo->findOneBy(['id' => $id]);
        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        // On récupère l'email du propriétaire, pour envoyer le mail.
        $emailUserOwner = $franchise->getUserOwner()->getEmail();

        // On appel la méthode pour changer l'état de la franchise.
        $changeStateService->changeStateObject($franchise);

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
            'newStateFranchise' => $changeStateService->getNewStateObject(),
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
        $id,
        $idGP,
        ManagerRegistry $doctrine,
        EmailService $emailService,
        LoggerService $loggerService
    ) {
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Franchise::class);

        // On récupère la franchise correspondant à l'id en paramètre et on vérifie qu'elle existe
        $franchise = $repo->findOneBy(['id' => $id]);
        if (empty($franchise)) {
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
        $globalPermissionAtChange = [];
        foreach ($permissions as $value) {
            if ($idGP == $value->getId()) {
                $globalPermissionAtChange = $value;
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
        if (in_array($idGP, $arrayGlobalPermissionFranchise)) {
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
        ], 200);
    }




    /**
     * PERMET DE SUPPRIMER UNE FRANCHISE AINSI QUE :
     * - Son propriétaire
     * - Ses structures
     * - Ses permissions globales
     * - Les gestionnaires des structures
     * - Les photos de profil des utilisateurs en lien avec la franchise
     * 
     * @return Response Json
     */
    #[Route('/supprimer-franchise-{id<\d+>}', name: 'app_supprimer_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteFranchise(
        $id,
        EmailService $emailService,
        LoggerService $loggerService,
        FranchiseRepository $franchiseRepo,
    ) {
        $franchise =  $franchiseRepo->findOneBy(['id' => $id]);
        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        // On récupère l'avatar du propriétaire puis on ajoute les avatars des gestionnaires.
        $avatars = [$franchise->getUserOwner()->getAvatar()];
        foreach ($franchise->getStructures() as $structure) {
            array_push($avatars, $structure->getUserAdmin()->getAvatar());
        }

        // On supprime tous les avatars du serveur.
        foreach ($avatars as $avatar) {
            // On vérifie que la photo n'est pas l'avatar par defaut.
            if ($avatar != 'avatar-defaut.jpg') {
                // Path de la photo.
                $directoryAvatar = $this->getParameter('avatar_directory');
                $pathAvatar = $directoryAvatar . '/' . $avatar;

                // On vérifie que la photo existe bien dans le serveur pour la supprimer.
                if (file_exists($pathAvatar)) {
                    unlink($pathAvatar);
                }
            }
        }

        // On supprime la franchise est tous ce qui est en lien avec.
        try {
            $franchiseRepo->remove($franchise, true);

            $this->addFlash(
                'success',
                'La franchise a bien été supprimée'
            );
            //return $this->redirectToRoute('app_list_franchise');
        } catch (\Exception $e) {
            $loggerService->logGeneric($e, 'Erreur persistance des données');

            return $this->json([
                'code' => 500,
                'message' => 'Erreur de suppression de la franchise',
                'idFranchise' => $franchise->getId(),
                'errorNumber' => $loggerService->getErrorNumber(),
            ], 500);
        }

        // On envoi un email au franchisé et à chaque gestionnaire
        try {
            $emailService->sendEmail(
                $franchise->getUserOwner()->getEmail(),
                'Votre compte a été supprimée',
                [
                    'user' => $franchise->getUserOwner(),
                ],
                'emails/remove-account.html.twig'
            );
            foreach ($franchise->getStructures() as $structure) {
                $emailService->sendEmail(
                    $structure->getUserAdmin()->getEmail(),
                    'Votre compte a été supprimée',
                    [
                        'user' => $structure->getUserAdmin(),
                    ],
                    'emails/remove-account.html.twig'
                );
            }
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
            'message' => 'Franchise supprimée avec succès',
        ], 200);
    }




    /**
     * PERMET DE MODIFIER UNE FRANCHISE :
     * - Le nom de la franchise
     * 
     * @return Response Json
     */
    #[Route('/modifier-franchise-{id<\d+>}', name: 'app_modifierfranchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function editFranchise(
        $id,
        LoggerService $loggerService,
        FranchiseRepository $franchiseRepo,
        Request $request,
        ManagerRegistry $doctrine,
    ) {
        $em = $doctrine->getManager();

        // On récupère la franchise à modifier
        $franchise =  $franchiseRepo->findOneBy(['id' => $id]);
        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        // On récupère les params de la requete Ajax, le nom est celui indiqué dans l'attribut data-request de la balise HTML.
        $paramNameFranchise = $request->get('namefranchise');

        if (!empty($paramNameFranchise) && isset($paramNameFranchise)) {
            // On vérifie que le nom n'est pas déja pris.
            $checkValue = $franchiseRepo->findOneBy(['name' => $paramNameFranchise]);

            if (!empty($checkValue)) {
                return new JsonResponse([
                    'alreadyExists' => 'Ce nom existe déjà'
                ], 409);
            }
            $franchise->setName($paramNameFranchise);
            $em->persist($franchise);
        }

        // On modifie la franchise.
        try {
            $em->flush();
        } catch (\Exception $e) {
            $loggerService->logGeneric($e, 'Erreur persistance des données');

            return $this->json([
                'code' => 500,
                'message' => 'Erreur de suppression de la franchise',
                'idFranchise' => $franchise->getId(),
                'errorNumber' => $loggerService->getErrorNumber(),
            ], 500);
        }

        return new JsonResponse([
            'code' => 200,
            'message' => 'Franchise modifiée avec succès'
        ], 200);
    }
}