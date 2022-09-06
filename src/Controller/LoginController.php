<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\CreatePasswordType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;


class LoginController extends AbstractController
{
    /**
     * Permet à l'utilisateur de se connecter.
     * Complément de vérification dans la class UserChecker
     * 
     * @return Response
     */
    #[Route('/connexion', name: 'app_connexion')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {   
        // Si l'utilisateur est connecté, on le redirige vers la page profil
        if($this->getUser()) {
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
     * Permet à l'utilisateur de créer ou réinitaliser son mot de passe.
     * Active le compte de l'utilisateur après avoir crée son mot de passe.
     * 
     * @return Response
     */
    #[Route('/creer-mot-de-passe/{token}', name: 'app_creer_mot_de_passe')]
    public function activationUser(
        ManagerRegistry $doctrine, 
        $token, 
        Request $request,
        UserPasswordHasherInterface $passwordHasher
        ): Response 
    {
        // Si l'utilisateur est connecté, on le redirige vers la page profil
        if($this->getUser()) {
            return $this->redirectToRoute('app_profil');
        }

        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(User::class);
        $user = $repo->findOneBy(['activationToken' => $token]);

        $formCreatePassword = $this->createForm(CreatePasswordType::class, $user);
        $formCreatePassword->handleRequest($request);
        
        // Si le token n'existe pas on redirige vers une 404
        if($user == []) {
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
    
            $em->persist($user);
            $em->flush();
    
            $this->addFlash(
                'success',
                'Vous pouvez vous connecter 💪'
            );
    
            return $this->redirectToRoute('app_connexion');
        }


        return $this->render('login/create-password.html.twig', [
            'formCreatePassword' => $formCreatePassword->createView()
        ]);
    }

    /**
     * Deconnecte l'utilisateur et le renvoi vers la page de connexion
     * @return void
     */
    #[Route('/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {

    }


    /**
     * Permet à l'utilisateur de réinitialiser son mot de passe.
     *
     * @return Response
     */
    #[Route('/mot-de-passe-perdu', name: 'app_mot_de_passe_perdu')]
    public function resetPassword(
        Request $request, 
        ManagerRegistry $doctrine,
        MailerInterface $mailer, 
        ): Response 
    {
        // Si l'utilisateur est connecté, on le redirige vers la page profil
        if($this->getUser()) {
            return $this->redirectToRoute('app_profil');
        }

        $repo = $doctrine->getRepository(User::class);
        $em = $doctrine->getManager();

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
            if($user != [] ) {   
                // On crée un nouveau token en BDD
                $user->setActivationToken(md5(uniqid()));

                $em->persist($user);
                $em->flush();

                // Envoi d'un email avec un lien pour changer le mot de passe
                $sendEmail = new TemplatedEmail();
                    $sendEmail->from('BodyCool <noreply@bodycool.com>');
                    $sendEmail->to($email);
                    $sendEmail->replyTo('noreply@bodycool.com');
                    $sendEmail->subject('Modifier votre mot de passe');
                    $sendEmail->context([
                        'user' => $user,
                    ]);
                    $sendEmail->htmlTemplate('emails/reset-password.html.twig');
                $mailer->send($sendEmail);

                $this->addFlash(
                    'success',
                    'Vous allez recevoir un mail pour réinitialiser votre mot de passe'
                );
            } else {
                $this->addFlash(
                    'notice',
                    'Ce compte est désactivé ou inexistant'
                );
            }
        }

        return $this->render('login/reset-password.html.twig', [
            'formResetPassword' => $formResetPassword->createView()
        ]);
    }

}
