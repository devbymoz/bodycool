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

/**
 * Gère les routes à prendre si l'utilisateur n'est pas connecté
 * Connexion, deconnexion, création du mot de passe, mot de passe perdu
 */
class LoginController extends AbstractController
{
    /**
     * Connexion de l'utilisateur
     * @return Response
     */
    #[Route('/connexion', name: 'app_connexion')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {   
        if($this->getUser()) {
            return $this->redirectToRoute('app_ajouter.franchise');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastAuthenticationError();   
        
        return $this->render('login/connexion.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }


    /**
     * Permet à l'utilisateur de créer son mot de passe et d'activer son compte
     * @return Response
     */
    #[Route('/creer-mot-de-passe/{token}', name: 'app_creer.mot.de.passe')]
    public function activationUser(
        ManagerRegistry $doctrine, 
        $token, 
        Request $request,
        UserPasswordHasherInterface $passwordHasher
        ): Response 
    {
        if($this->getUser()) {
            return $this->redirectToRoute('app_ajouter.franchise');
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
            $user->setActivationToken('');
    
            $em->persist($user);
            $em->flush();
    
            $this->addFlash(
                'success',
                'Vous pouvez vous connecter maintenant'
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
     * Renvoi un lien à l'utilisateur pour modifier son mot de passe
     */
    #[Route('/mot-de-passe-perdu', name: 'app_mot.de.passe.perdu')]
    public function resetPassword(
        Request $request, 
        ManagerRegistry $doctrine,
        MailerInterface $mailer, 
        ): Response 
    {
        if($this->getUser()) {
            return $this->redirectToRoute('app_ajouter.franchise');
        }

        $repo = $doctrine->getRepository(User::class);
        $em = $doctrine->getManager();

        $formResetPassword = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'Renseignez votre adresse email de connexion',
                'attr' => array(
                    'placeholder' => 'Indiquez votre adresse email'
                )
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
                    $sendEmail->from('noreply@bodycool.com');
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
