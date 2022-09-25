<?php

namespace App\Controller\Structure;

use App\Entity\Structure;
use App\Entity\User;
use App\Form\Structure\AddStructureType;
use App\Service\EmailService;
use App\Service\LoggerService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


/**
 * CRÉATION, MISE À JOUR, SUPRESSION DES STRUCTURES
 * 
 */
#[Route('/structures')]
class CudStructureController extends AbstractController
{

    /**
     * CRÉATION D'UNE NOUVELLE STRUCTURE
     * - Crée une nouvelle structure.
     * - Crée un nouvel utilisateur qui sera le gestionnaire de la structure.
     * 
     * @return Response
     */
    #[Route('/ajouter-structure', name: 'app_ajouter_structure')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        ManagerRegistry $doctrine,
        EmailService $emailService,
        LoggerService $loggerService,
    ): Response {
        $em = $doctrine->getManager();

        // On récupère les repo nécessaire.
        $repoUser = $doctrine->getRepository(User::class);
        $repoStructure = $doctrine->getRepository(Structure::class);

        $form = $this->createForm(AddStructureType::class,);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userAdmin = $data->getUserAdmin();
            $userOwner = $data->getFranchise()->getUserOwner();

            // On vérifie que l'utilisateur n'existe pas déja.
            $emailAdmin = $userAdmin->getEmail();
            $checkEmail = $repoUser->findOneBy(['email' => $emailAdmin]);

            // On vérifie que le nom de la structure n'existe pas déja.
            $nameStructure = $data->getName();
            $checkNameStructure = $repoStructure->findOneBy(['name' => $nameStructure]);

            // On vérifie que le numéro de contrat n'existe pas déjà.
            $contractNumber = $data->getContractNumber();
            $checkContractNumber = $repoStructure->findOneBy(['contractNumber' => $contractNumber]);

            if ($checkEmail != []) {
                $this->addFlash(
                    'notice',
                    'Cet utilisateur existe déjà'
                );
            } elseif ($checkNameStructure != []) {
                $this->addFlash(
                    'notice',
                    'Cette structure existe déjà'
                );
            } elseif ($checkContractNumber != []) {
                $this->addFlash(
                    'notice',
                    'Un contrat avec ce numéro existe déjà'
                );
            } else {
                // On attribue le role Gestionnaire à l'utilisateur.
                $userAdmin->setRoles(['ROLE_GESTIONNAIRE']);

                try {
                    $em->persist($data);
                    $em->flush();

                    $this->addFlash(
                        'success',
                        'La structure a bien été créée'
                    );
                } catch (\Exception $e) {
                    $loggerService->logGeneric($e, 'Erreur persistance des données');

                    $this->addFlash(
                        'exception',
                        'La strucutre n\'a pas pu être enregistrée en BDD. Log n° : ' . $loggerService->getErrorNumber()
                    );
                }

                // On envoi un email au gestionnaire pour qu'il confirme son compte.
                try {
                    $emailService->sendEmail(
                        $emailAdmin,
                        'Confirmer votre compte Gestionnaire BodyCool',
                        [
                            'user' => $userAdmin,
                            'data' => $data
                        ],
                        'emails/confirm-structure.html.twig'
                    );
                } catch (TransportExceptionInterface $e) {
                    $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');

                    $this->addFlash(
                        'exception',
                        'L\'email n\'a pas pu être envoyé au propriétaire. Log n° : ' . $loggerService->getErrorNumber()
                    );
                }

                // On envoi un email au franchisé pour lui indiquer que sa structure a bien été créée.
                try {
                    $emailService->sendEmail(
                        $userOwner->getEmail(),
                        'Votre nouvelle structure a bien été créée',
                        [
                            'userOwner' => $userOwner,
                            'userAdmin' => $userAdmin,
                            'data' => $data
                        ],
                        'emails/new-structure.html.twig'
                    );
                } catch (TransportExceptionInterface $e) {
                    $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');

                    $this->addFlash(
                        'exception',
                        'L\'email n\'a pas pu être envoyé au franchisé. Log n° : ' . $loggerService->getErrorNumber()
                    );
                }
            }
        }

        return $this->renderForm('structure/add-structure.html.twig', [
            'form' => $form
        ]);
    }




    /**
     * PERMET D'ACTIVER OU DÉSACTIVER UNE STRUCTURE
     * 
     * @return Response Json
     */
    #[Route('/changer-etat-{id<\d+>}', name: 'app_changer_etat_structure')]
    #[IsGranted('ROLE_ADMIN')]
    public function changeStateFranchise(
        $id, 
        ManagerRegistry $doctrine, 
        EmailService $emailService,
        LoggerService $loggerService
        ): Response
	{
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Structure::class);

        // On récupère la structure correspondant à l'id en paramètre.
        $structure = $repo->findOneBy(['id' => $id]);
        if (empty($structure)) {
            throw $this->createNotFoundException('Cette structure n\'existe pas.');
        }
        
        // On récupère le gestionnaire et le franchisé.
        $userAdminStructure = $structure->getUserAdmin();
        $userOwner = $structure->getFranchise()->getUserOwner();
            
        // On récupère l'état de la structure (activée ou désactivée).
        $stateStructure = $structure->isActive();
        
        // On inverse l'état de la structure.
        $structure->setActive(!$stateStructure);
        
        // On sauvegarde le nouvel état dans une variable
        $newStateStructure = $structure->isActive();

        // On sauvegarder le nouvel état de la structure en BDD
        try {
            $em->flush();
        } catch (\Exception $e) {
            $loggerService->logGeneric($e, 'Erreur persistance des données');

            return $this->json([
                'code' => 500, 
                'message' => 'Erreur de persistance des données',
                'structureName' => $structure->getName(), 
                'errorNumber' => $loggerService->getErrorNumber()
            ], 500);
        }

        // On envoi un email au franchisé pour lui indiquer qu'un de ces structures a été changées.
        try {
            $emailService->sendEmail(
                $userOwner->getEmail(), 
                'Votre structure a été modifiée', 
                ['structure' => $structure, 'userOwner' => $userOwner], 
                'emails/change-state-structure-owner.html.twig'
            );

            // On envoi un email au gestionnaire pour lui indiquer que la structure qu'il gère à changée.
            $emailService->sendEmail(
                $userAdminStructure->getEmail(), 
                'La structure que vous gérez a été modifiée', 
                ['structure' => $structure, 'userAdminStructure' => $userAdminStructure], 
                'emails/change-state-structure-admin.html.twig'
            );
        } catch (TransportExceptionInterface $e) {
            $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');

            return $this->json([
                'code' => 500, 
                'message' => 'Erreur de distribution du mail.',
                'idStructure' => $structure->getId(),
                'errorNumber' => $loggerService->getErrorNumber(),
            ], 500);
        }


        return $this->json([
            'code' => 200, 
            'message' => 'Structure modifiée avec success',
            'newStateStructure' => $newStateStructure,
            'structureName' => $structure->getName()
        ], 200);
    }















    
}
