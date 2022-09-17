<?php 

namespace App\Controller\User;

use App\Form\User\EditAvatarType;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;


/**
 * LECTURE, MISE À JOUR ET SUPPRESSION DE L'UTILISATEUR
 * 
 */
#[Route('/profil')]
class RudUserController extends AbstractController
{
    /**
     * AFFICHE LE PROFIL DE L'UTILISATEUR
     * L'utilisateur peut modifier sa photo de profil.
     *
     * @return Response
     */
    #[Route('/', name: 'app_profil')]
    #[IsGranted('ROLE_USER')]
    public function index(
        ManagerRegistry $doctrine, 
        Request $request, 
        SluggerInterface $slugger,
        LoggerService $loggerService
        ): Response
    {
        $user = $this->getUser();
        $em = $doctrine->getManager();

        $formEditUser = $this->createForm(EditAvatarType::class, $user);
        $formEditUser->handleRequest($request);
        
        // Path de la photo de l'utilisateur actuelle.
        $directoryAvatar = $this->getParameter('avatar_directory');
        $currentAvatar = $user->getAvatar();
        $pathAvatar = $directoryAvatar .'/' . $currentAvatar;
        

        if ($formEditUser->isSubmitted() && $formEditUser->isValid()) {
            $avatar = $formEditUser->get('avatar')->getData();
                
            // Verifie que le fichier est bien téléchargé sur le serveur.
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
                    $loggerService->logGeneric($e, 'Erreur fichier télécharger');

                     $this->addFlash(
                        'exception',
                        'La photo n\'a pas pu être enregistrée, merci de nous communiquer le n° d\'erreur suivant : ' . $loggerService->getErrorNumber()
                    );
                }
            }
                
            // Suppression de l'ancienne photo du serveur.
            if (file_exists($pathAvatar) && $currentAvatar != 'avatar-defaut.jpg') {
                unlink($pathAvatar);
            }
    
            // Ajout de la nouvelle photo dans la BDD
            try {
                $user->setAvatar($newFilename);
                $em->persist($user);
                $em->flush();
            
                $this->addFlash(
                    'success',
                    'Votre photo a été modifié'
                );
        
                return $this->redirectToRoute('app_profil');
            } catch (\Exception $e) {
                $loggerService->logGeneric($e, 'Erreur persistance des données');

                $this->addFlash(
                    'exception',
                    'La photo n\'a pas pu être enregistrée en BDD, merci de nous communiquer le n° d\'erreur suivant : ' . $loggerService->getErrorNumber()
                );
            }
        }

        return $this->renderForm('user/profil.html.twig', [
            'user' => $user,
            'formEditUser' => $formEditUser
        ]);
    }




    /**
     * PERMET DE SUPPRIMER LA PHOTO DE PROFIL
     * 
     * @return Response Json
     */
    #[Route('/supprimer-avatar', options: ['expose' => true], name: 'app_supprimer_avatar')]
    #[IsGranted('ROLE_USER')]
    public function changeAvatarUser(
        ManagerRegistry $doctrine, 
        LoggerService $loggerService
        ): Response
	{
        $user = $this->getUser();
        $em = $doctrine->getManager();

        // Path de la photo de l'utilisateur actuelle.
        $directoryAvatar = $this->getParameter('avatar_directory');
        $currentAvatar = $user->getAvatar();
        $pathAvatar = $directoryAvatar .'/' . $currentAvatar;
        
        // Si la photo de profil est celle par defaut.
        if ($currentAvatar === 'avatar-defaut.jpg') {
            return false;
        // On vérifie que la photo existe bien  
        } elseif (file_exists($pathAvatar)) {
            // On supprime la photo et on attribut la photo par defaut
            unlink($pathAvatar);
            $user->setAvatar('avatar-defaut.jpg');

            try {
                $em->persist($user);
                $em->flush();
    
                return $this->redirectToRoute('app_profil');
            } catch (\Exception $e) {
                $loggerService->logGeneric($e, 'Erreur persistance des données');

                return $this->json([
                    'code' => 500, 
                    'message' => 'Erreur d\'enregistrement de la photo dans la BDD.',
                    'idUser' => $user->getId(),
                    'errorNumber' => $loggerService->getErrorNumber(),
                ], 500);
            }
        }

        return $this->json([
            'code' => 200, 
            'message' => 'Photo supprimée avec success',
            'idUser' => $user->getId(),
        ], 200);
    }






}   
      