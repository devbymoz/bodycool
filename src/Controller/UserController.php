<?php

namespace App\Controller;

use App\Form\EditUserType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class UserController extends AbstractController
{
    /**
     * Affiche le profil de l'utilisateur.
     * L'utilisateur peut modifier sa photo de profil.
     *
     * @return Response
     */
    #[Route('/profil', name: 'app_profil')]
    #[IsGranted('ROLE_USER')]
    public function index(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $em = $doctrine->getManager();

        $formEditUser = $this->createForm(EditUserType::class, $user);
        $formEditUser->handleRequest($request);
        
        // Path de la photo de l'utilisateur actuelle.
        $directoryAvatar = $this->getParameter('avatar_directory');
        $currentAvatar = $user->getAvatar();
        $pathAvatar = $directoryAvatar .'/' . $currentAvatar;
        
        // Si le bouton save a été cliqué (mettre à jour).
        if ($formEditUser->getClickedButton() && 'save' === $formEditUser->getClickedButton()->getName()) {
            if ($formEditUser->isSubmitted() && $formEditUser->isValid()) {
                $avatar = $formEditUser->get('avatar')->getData();
                
                // Verifie que le fichier est bien téléchargé.
                if ($avatar) {
                    $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
                    
                    // Sécurité pour inclure le nom du fichier dans l'url.
                    $safeFilename = $slugger->slug($originalFilename);

                    // Renomme le fichier pour qu'il est un nom unique.
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$avatar->guessExtension();
                    
                    // Déplace le fichier dans le répertoire des avatars
                    try {
                        $avatar->move(
                            $this->getParameter('avatar_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        die ('Le fichier n\'a pas été importé : ' . $e->getMessage());
                    }
                }
                
                // Suppression de l'ancienne photo du serveur.
                if (file_exists($pathAvatar) && $currentAvatar != 'avatar-defaut.jpg') {
                    unlink($pathAvatar);
                }
    
                // Ajout de la nouvelle photo dans la BDD
                $user->setAvatar($newFilename);
                $em->persist($user);
                $em->flush();
    
                $this->addFlash(
                    'success',
                    'Votre photo a été modifié'
                );

                return $this->redirectToRoute('app_profil');
            }
        }

        // Si le bouton removeAvatar a été cliqué (supprimer la photo)
        if ($formEditUser->getClickedButton() && 'removeAvatar' === $formEditUser->getClickedButton()->getName()) {

            // Si la photo de profil est celle par defaut.
            if ($currentAvatar === 'avatar-defaut.jpg') {
                $this->addFlash(
                    'notice',
                    'Impossible de supprimer l\'avatar par default'
                );
            // On vérifie que la photo existe bien  
            } elseif (file_exists($pathAvatar)) {
                // On supprime la photo et on assigne la photo par defaut
                unlink($pathAvatar);
                $user->setAvatar('avatar-defaut.jpg');

                $em->persist($user);
                $em->flush();
    
                $this->addFlash(
                    'success',
                    'Votre photo a bien été supprimée'
                );

                return $this->redirectToRoute('app_profil');
            }
        }

        return $this->render('user/profil.html.twig', [
            'user' => $user,
            'formEditUser' => $formEditUser->createView()
        ]);
    }

    
}