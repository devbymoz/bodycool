<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\CreatePasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Gère les routes à prendre si l'utilisateur n'est pas connecté
 */
class LoginController extends AbstractController
{
    /**
     * Connexion de l'utilisateur
     * @return Response
     */
    #[Route('/connexion', name: 'app_connexion')]
    public function login(): Response
    {
        return $this->render('login/connexion.html.twig');
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




    

}
