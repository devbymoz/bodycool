<?php

namespace App\Controller\User;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\User\CreatePasswordType;
use App\Service\EmailService;
use App\Service\LoggerService;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Exception;


class LoginUserController extends AbstractController
{


    /**
     * CONNEXION DE L'UTILISATEUR
     * Complément de vérification dans la class UserChecker (Security)
     * 
     * @return Response
     */
    #[Route('/connexion', name: 'app_connexion')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est connecté, on le redirige vers la page profil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profil');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastAuthenticationError();

        return $this->render('login/connexion.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }




    /**
     * CRÉATION ET RÉINITIALISATION DU MOT DE PASSE
     * Active le compte de l'utilisateur après avoir crée son mot de passe.
     * 
     * @return Response
     */
    #[Route('/creer-mot-de-passe/{token}', name: 'app_creer_mot_de_passe')]
    public function activationUser(
        ManagerRegistry $doctrine,
        $token,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        LoggerService $loggerService
    ): Response {
        // Si l'utilisateur est connecté, on le redirige vers la page profil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profil');
        }

        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(User::class);
        $user = $repo->findOneBy(['activationToken' => $token]);

        $formCreatePassword = $this->createForm(CreatePasswordType::class, $user);
        $formCreatePassword->handleRequest($request);

        // Si le token n'existe pas on redirige vers une 404
        if ($user == []) {
            throw $this->createNotFoundException(sprintf('Clé d\'activation incorrect'));
        } elseif ($formCreatePassword->isSubmitted() && $formCreatePassword->isValid()) {
            $data = $formCreatePassword->getData();
            $plaintextPassword = $data->getPlainPassword();

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plaintextPassword
            );

            $user->setPassword($hashedPassword);
            $user->setActive(true);
            $user->setActivationToken(null);

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Vous pouvez vous connecter 💪'
                );

                return $this->redirectToRoute('app_connexion');
            } catch (Exception $e) {
                $loggerService->logGeneric($e, 'Erreur persistance des données');

                $this->addFlash(
                    'exception',
                    'Le mot de passe n\'a pas pu être crée, merci de nous communiquer le n° d\'erreur suivant : ' . $loggerService->getErrorNumber()
                );
            }
        }

        return $this->renderForm('login/create-password.html.twig', [
            'formCreatePassword' => $formCreatePassword
        ]);
    }




    /**
     * DECONNEXION DE L'UTILISATEUR
     * @return void
     */
    #[Route('/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
    }



    /**
     * RÉINITIALISATION DU MOT DE PASSE
     *
     * @return Response
     */
    #[Route('/mot-de-passe-perdu', name: 'app_mot_de_passe_perdu')]
    public function resetPassword(
        Request $request,
        ManagerRegistry $doctrine,
        EmailService $emailService,
        LoggerService $loggerService,
    ): Response {
        // Si l'utilisateur est connecté, on le redirige vers la page profil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profil');
        }
      
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(User::class);

        // Formulaire de réinitialisation du mot de passe
        $formResetPassword = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'Renseignez votre adresse email de connexion',
                'attr' => array(
                    'placeholder' => 'Indiquez votre adresse email',
                    'class' => 'input'
                ),
                'constraints' => [
                    new NotBlank(),
                    new Email()
                ]
            ])
            ->getForm();
        $formResetPassword->handleRequest($request);

        if ($formResetPassword->isSubmitted() && $formResetPassword->isValid()) {
            $data = $formResetPassword->getData();
            $email = $data['email'];
            $user = $repo->findOneBy(['email' => $email, 'active' => true]);

            // Si l'adresse email existe et que le compte est bien activé
            if ($user != []) {
                // On crée un nouveau token en BDD
                $user->setActivationToken(md5(uniqid()));

                try {
                    $em->persist($user);
                    $em->flush();
                } catch (Exception $e) {
                    $loggerService->logGeneric($e, 'Erreur persistance des données');

                    $this->addFlash(
                        'exception',
                        'Une erreur est survenue, merci de nous communiquer le n° d\'erreur suivant : ' . $loggerService->getErrorNumber()
                    );
                }

                // Envoi d'un email avec un lien pour changer le mot de passe
                try {
                    $emailService->sendEmail(
                        $email,
                        'Modifier votre mot de pass',
                        [
                            'user' => $user,
                        ],
                        'emails/reset-password.html.twig'
                    );

                    $this->addFlash(
                        'success',
                        'Vous allez recevoir un mail pour réinitialiser votre mot de passe'
                    );
                } catch (TransportExceptionInterface $e) {
                    $loggerService->logGeneric($e, 'Erreur lors de l\'envoi du mail');

                    $this->addFlash(
                        'exception',
                        'L\'email n\'a pas pu être envoyé, merci de nous communiquer le n° d\'erreur suivant : ' . $loggerService->getErrorNumber()
                    );
                }
            } else {
                $this->addFlash(
                    'notice',
                    'Ce compte est désactivé ou inexistant'
                );
            }
        }

        return $this->renderForm('login/reset-password.html.twig', [
            'formResetPassword' => $formResetPassword
        ]);
    }
}
