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
    #[Route('/profil', name: 'app_profil')]
    #[IsGranted('ROLE_USER')]
    public function index(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $em = $doctrine->getManager();

        $formEditUser = $this->createForm(EditUserType::class, $user);
        $formEditUser->handleRequest($request);
        
        // Information sur la photo actuel
        $directoryAvatar = $this->getParameter('avatar_directory');
        $oldAvatar = $user->getAvatar();
        $pathAvatar = $directoryAvatar .'/' . $oldAvatar;

        /**
         * Si le bouton save a été cliqué
         */
        if ($formEditUser->getClickedButton() && 'save' === $formEditUser->getClickedButton()->getName()) {
            if ($formEditUser->isSubmitted() && $formEditUser->isValid()) {
                $avatar = $formEditUser->get('avatar')->getData();
                
                // Verifie que le fichier est bien téléchargé
                if ($avatar) {
                    $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
                    
                    // Sécurité pour inclure le nom du fichier dans l'url
                    $safeFilename = $slugger->slug($originalFilename);
                    // Renomme le fichier pour qu'il est un nom unique
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
                
                // On supprime l'ancienne photo du serveur
                if (file_exists($pathAvatar) && $oldAvatar != 'avatar-defaut.jpg') {
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

        /**
         * Si le bouton pour removeAvatar la photo a été cliqué
         */
        if ($formEditUser->getClickedButton() && 'removeAvatar' === $formEditUser->getClickedButton()->getName()) {

            // On vérifie que la photo n'est pas celle par defaut
            if ($user->getAvatar() === 'avatar-defaut.jpg') {
                $this->addFlash(
                    'notice',
                    'Impossible de supprimer l\'avatar par default'
                );         
            } elseif (file_exists($pathAvatar) && $oldAvatar != 'avatar-defaut.jpg') {        
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