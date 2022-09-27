<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\User\UserType;
use App\Service\EmailService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


/**
 * CRÉATION D'UN NOUVEL UTILISATEUR
 * 
 */

class CreateUserController extends AbstractController
{

    /**
     * PAGE POUR CREÉR UN NOUVEL UTILISATEUR.
     * 
     *
     * @return Response
     */
    #[Route('/ajouter-utilisateur', name: 'app_ajouter_utilisateur')]
    #[IsGranted('ROLE_ADMIN')]
    public function addNewUser(
        ManagerRegistry $doctrine,
        Request $request,
        EmailService $emailService,
        LoggerService $loggerService 
    ): Response {
        $em = $doctrine->getManager();

        $repoUser = $doctrine->getRepository(User::class);
        $user = new User();

        $form = $this->createForm(UserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            // Si l'utilisateur qui n'est pas un super admin, il ne peut pas créer de super admin.
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->addFlash(
                    'notice',
                    'Seul un Super Admin peut créer un compte Super Admin'
                );
            } else {
                // Vérifie si l'email existe déja en BDD.
                $email = $user->getEmail();
                $checkEmail = $repoUser->findOneBy(['email' => $email]);
    
                if ($checkEmail != []) {
                    $this->addFlash(
                        'notice',
                        'Cet email existe déjà'
                    );
                } else { 
                    try {
                        $em->persist($user);
                        $em->flush();
    
                        $this->addFlash(
                            'success',
                            'L\'utilisateur a bien été créée'
                        );
                    } catch (\Exception $e) {
                        $loggerService->logGeneric($e, 'Erreur persistance des données');
    
                        $this->addFlash(
                            'exception',
                            'L\'utilisateur  n\'a pas pu être enregistrée en BDD. Log n° : ' . $loggerService->getErrorNumber()
                        );
                    }
    
                    // On envoi un email à l'utilisateur pour qu'il confirme son compte.
                    try {
                        $emailService->sendEmail(
                            $email,
                            'Confirmer votre compte BodyCool',
                            [
                                'user' => $user,
                            ],
                            'emails/confirm-account.html.twig'
                        );
                    } catch (TransportExceptionInterface $e) {
                        $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');
    
                        $this->addFlash(
                            'exception',
                            'L\'email n\'a pas pu être envoyé à l\'utiliateur. Log n° : ' . $loggerService->getErrorNumber()
                        );
                    }
                }
            }
        }

        return $this->renderForm('user/add-user.html.twig', [
            'form' => $form
        ]);
    }
}
