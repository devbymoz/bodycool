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
}
