<?php

namespace App\Controller\Structure;

use App\Entity\Franchise;
use App\Entity\Structure;
use App\Entity\User;
use App\Form\Structure\AddStructureType;
use App\Repository\StructureRepository;
use App\Service\ChangeStateService;
use Exception;
use App\Service\EmailService;
use App\Service\LoggerService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;


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
                    'Cet email existe déjà'
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

                // On crée l'url de la franchise.
                $slugger = new AsciiSlugger();
                $slug = $slugger->slug(strtolower($data->getName()));
                $data->setSlug($slug);
                try {
                    $em->persist($data);
                    $em->flush();

                    $this->addFlash(
                        'success',
                        'La structure a bien été créée'
                    );
                } catch (Exception $e) {
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

                    // On envoi un email au franchisé pour lui indiquer que sa structure a bien été créée.
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
                        'L\'email n\'a pas pu être envoyé. Log n° : ' . $loggerService->getErrorNumber()
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
    #[Route('/changer-etat-{id<\d+>}', options: ['expose' => true], name: 'app_changer_etat_structure')]
    #[IsGranted('ROLE_ADMIN')]
    public function changeStateStructure(
        $id,
        ManagerRegistry $doctrine,
        EmailService $emailService,
        LoggerService $loggerService,
        ChangeStateService $changeStateService
    ): Response {
        $repo = $doctrine->getRepository(Structure::class);

        // On récupère la structure correspondant à l'id en paramètre.
        $structure = $repo->findOneBy(['id' => $id]);
        if (empty($structure)) {
            throw $this->createNotFoundException('Cette structure n\'existe pas.');
        }

        // On récupère le gestionnaire et le franchisé.
        $userAdminStructure = $structure->getUserAdmin();
        $userOwner = $structure->getFranchise()->getUserOwner();

        // On appel le service pour changer l'état d'un objet.
        $changeStateService->changeStateObject($structure);

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
            'structureName' => $structure->getName()
        ], 200);
    }



    /**
     * PERMET DE SUPPRIMER UNE STRUCTURE AINSI QUE :
     * - Son gestionnaire
     * - La photo de profil du gestionnaire
     * 
     * @return Response Json
     */
    #[Route('/supprimer-structure-{id<\d+>}', name: 'app_supprimer_structure')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteStructure(
        $id,
        EmailService $emailService,
        LoggerService $loggerService,
        StructureRepository $structureRepo,
    ) {
        $structure =  $structureRepo->findOneBy(['id' => $id]);
        if (empty($structure)) {
            throw $this->createNotFoundException('Cette structure n\'existe pas.');
        }

        // On récupère l'avatar du gestionnaire.
        $avatar = $structure->getUserAdmin()->getAvatar();

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

        // On supprime la structure est tous ce qui est en lien avec.
        try {
            $structureRepo->remove($structure, true);

            $this->addFlash(
                'success',
                'La structure a bien été supprimée'
            );
        } catch (Exception $e) {
            $loggerService->logGeneric($e, 'Erreur persistance des données');

            return $this->json([
                'code' => 500,
                'message' => 'Erreur de suppression de la structure',
                'idFranchise' => $structure->getId(),
                'errorNumber' => $loggerService->getErrorNumber(),
            ], 500);
        }

        // On envoi un email au franchisé et au gestionnaire.
        try {
            $emailService->sendEmail(
                $structure->getFranchise()->getUserOwner()->getEmail(),
                'Votre structure a été supprimée.',
                [
                    'user' => $structure->getFranchise()->getUserOwner(),
                    'structure' => $structure
                ],
                'emails/remove-structure.html.twig'
            );
            $emailService->sendEmail(
                $structure->getUserAdmin()->getEmail(),
                'Votre compte a été supprimée',
                [
                    'user' => $structure->getUserAdmin(),
                    'structure' => $structure
                ],
                'emails/remove-structure.html.twig'
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
            'message' => 'Structure supprimée avec succès',
        ], 200);
    }



    /**
     * PERMET DE LIER UNE STRUCTURE À UNE NOUVELLE FRANCHISE
     * 
     * @return Response Json
     */
    #[Route('/lier-structure-{id<\d+>}', options: ['expose' => true], name: 'app_lier_structure')]
    #[IsGranted('ROLE_ADMIN')]
    public function linkStructureWithFranchise(
        $id,
        EmailService $emailService,
        LoggerService $loggerService,
        ManagerRegistry $doctrine,
        Request $request,
    ): Response {
        $em = $doctrine->getManager();

        // On récupère la structure correspondant à l'id.
        $repoStructure = $doctrine->getRepository(Structure::class);
        $structure = $repoStructure->findOneBy(['id' => $id]);

        // Selection de la nouvelle franchise.
        $form = $this->createFormBuilder()
            ->add('name', EntityType::class, [
                'class' => Franchise::class,
                'choice_label' => 'name',
                'autocomplete' => true,
                'placeholder' => 'Choisissez une nouvelle franchise',
            ])
            ->getForm();

        // On vérifie que les paramètres pour traiter la requete sont bien présents.
        if ($request->get('ajax') && (!empty($request->get('idfr')))) {
            // On récupère la valeur du paramètre de l'id de la franchise.
            $idFranchise = $request->get('idfr');

            // On vérifie que l'id correspond bien à une franchise en BDD
            $repoFranchise = $doctrine->getRepository(Franchise::class);
            $newFranchise = $repoFranchise->findOneBy(['id' => $idFranchise]);

            if (empty($newFranchise) || !isset($newFranchise)) {
                return $this->json([
                    'code' => 404,
                    'message' => 'Aucune franchise ne correspond à cette id'
                ], 404);
            }

            // On persist la nouvelle franchise en BDD. 
            try {
                $structure->setFranchise($newFranchise);

                $em->persist($structure);
                $em->flush();    

                $this->addFlash(
                    'success',
                    'La structure à bien été modifiée'
                );
            } catch (Exception $e) {
                $loggerService->logGeneric($e, 'Erreur persistance des données');

                return $this->json([
                    'code' => 500,
                    'message' => 'Erreur de modification de la structure',
                    'idStructure' => $structure->getId(),
                    'errorNumber' => $loggerService->getErrorNumber(),
                ], 500);
            }

            // On envoi un mail au franchisé.
            try {
                $emailService->sendEmail(
                    $newFranchise->getUserOwner()->getEmail(),
                    'Votre nouvelle structure',
                    [
                        'user' => $newFranchise->getUserOwner(),
                        'structure' => $structure
                    ],
                    'emails/bind-new-franchise.html.twig'
                );
            } catch (TransportExceptionInterface $e) {
                $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');
    
                return $this->json([
                    'code' => 500,
                    'message' => 'Erreur de distribution du mail.',
                    'idNewFranchise' => $newFranchise->getId(),
                    'errorNumber' => $loggerService->getErrorNumber(),
                ], 500);
            }
            
            return $this->json([
                'code' => 200,
                'content' => $this->renderView('include/_change-structure.html.twig', [
                    'form' => $form->createView(),
                ]),
                'idFranchise' =>  $idFranchise,
            ], 200);
        } else {
            return $this->json([
                'code' => 200,
                'Message' =>  'Connexion ok',
                'content' => $this->renderView('include/_change-structure.html.twig', [
                    'form' => $form->createView(),
                ]),
            ], 200);
        }
    }



    /**
     * PERMET DE MODIFIER UNE STRUCTURE :
     * - Modifie le nom de la structure
     * 
     * @return Response Json
     */
    #[Route('/modifier-structure-{id<\d+>}', options: ['expose' => true], name: 'app_modifier_structure')]
    #[IsGranted('ROLE_ADMIN')]
    public function editStructure(
        $id,
        LoggerService $loggerService,
        StructureRepository $structureRepo,
        Request $request,
        ManagerRegistry $doctrine,
    ) {
        $em = $doctrine->getManager();

        // On récupère la franchise à modifier
        $structure =  $structureRepo->findOneBy(['id' => $id]);
        if (empty($structure)) {
            throw $this->createNotFoundException('Cette structure n\'existe pas.');
        }

        // On récupère les params de la requete Ajax, le nom est celui indiqué dans l'attribut data-request de la balise HTML.
        $paramNameStructure = $request->get('namestructure');

        if (!empty($paramNameStructure) && isset($paramNameStructure)) {
            // On vérifie que le nom n'est pas déja pris.
            $checkValue = $structureRepo->findOneBy(['name' => $paramNameStructure]);

            if (!empty($checkValue)) {
                return $this->json([
                    'alreadyExists' => 'Ce nom existe déjà'
                ], 409);
            }
            $structure->setName($paramNameStructure);
            $em->persist($structure);

            // On persist les nouvelles données.
            try {
                $em->flush();
                
                $this->addFlash(
                    'success',
                    'La structure à bien été modifiée'
                );
                return $this->json([
                    'code' => 200,
                    'message' => 'Structure modifiée avec succès'
                ], 200);
            } catch (Exception $e) {
                $loggerService->logGeneric($e, 'Erreur persistance des données');
    
                return $this->json([
                    'code' => 500,
                    'message' => 'Erreur de suppression de la structure',
                    'idStructure' => $structure->getId(),
                    'errorNumber' => $loggerService->getErrorNumber(),
                ], 500);
            }
        }

        return $this->json([
            'code' => 200,
            'message' => 'Connexion OK'
        ], 200);
    }





}
