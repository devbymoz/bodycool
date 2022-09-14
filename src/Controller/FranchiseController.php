<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Franchise;
use App\Entity\Permission;
use App\Form\ActiveFranchiseType;
use App\Form\AddFranchiseType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;


#[Route('/franchise')]
class FranchiseController extends AbstractController
{
    /**
     * Affiche la liste de toutes les franchises
     * 
     * @return Response
     */
    #[Route('/', name: 'app_list_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function listFranchise(
        Request $request, 
        ManagerRegistry $doctrine, 
        ): Response
    {
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Franchise::class);

        $franchises = $repo->findAll();
        $data = ['matchs' => $franchises];

        $form = $this->createFormBuilder($data)
            ->add('matchs', CollectionType::class,[
                'entry_type' =>  ActiveFranchiseType::class,
                ])
            ->getForm();

        $form->handleRequest($request); 
              
        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            //dd($data);
            $em->flush();
        }
                   
        
        return $this->renderForm('franchise/list-franchise.html.twig', [
            'form' => $form,                                 
        ]);
    } 
        


    /**
     * Formulaire qui permet :
     * - Créer une nouvelle franchise.
     * - Créer un nouvelle utilisateur qui sera le propriétaire de la franchise.
     * - Attribuer des permissions globales qui seront liées à la franchise.
     * 
     * @return Response
     */
    #[Route('/ajouter-franchise', name: 'app_ajouter_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function addFranchise(
        Request $request, 
        ManagerRegistry $doctrine, 
        MailerInterface $mailer, 
        LoggerInterface $logger
        ): Response
    {
        $em = $doctrine->getManager();
        
        $repoUser = $doctrine->getRepository(User::class);
        $repoFranchise = $doctrine->getRepository(Franchise::class);
        
        $formFranchise = $this->createForm(AddFranchiseType::class);
        $formFranchise->handleRequest($request);
            
        if($formFranchise->isSubmitted() && $formFranchise->isValid()) {
            $data = $formFranchise->getData();
            $user = $data->getUserOwner();
            
            // Vérifie si l'email existe déja en BDD.
            $email = $user->getEmail();
            $checkEmail = $repoUser->findOneBy(['email' => $email]);
            
            // Vérifie si le nom de la franchise existe déja en BDD.
            $nameFranchise = $data->getName();
            $checkNameFranchise = $repoFranchise->findOneBy(['name' => $nameFranchise]);
            
            if($checkEmail != []) {
                $this->addFlash(
                    'notice',
                    'Cet email existe déjà'
                );
            } elseif ($checkNameFranchise != []) {
                $this->addFlash(
                    'notice',
                    'Cette franchise existe déjà'
                );
            } else {
                // On attribue le role Franchise à l'utilisateur.
                $user->setRoles(['ROLE_FRANCHISE']);

                try {
                    $em->persist($data);
                    $em->flush();
                    
                    $this->addFlash(
                        'success',
                        'La franchise a bien été créée'
                    );
                } catch (\Exception $e) {
                    $errorNumber = uniqid();
                    $logger->error('Erreur persistance des données', [
                        'errorNumber' => $errorNumber,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);

                    $this->addFlash(
                        'exception',
                        'La franchise n\'a pas pu être enregistrée en BDD. Log n° : ' . $errorNumber
                    );
                }

                // On envoi un email au franchisé pour qu'il confirme son compte.
                try {
                    $sendEmail = new TemplatedEmail();
                        $sendEmail->from('BodyCool <noreply@bodycool.com>');
                        $sendEmail->to($email);
                        $sendEmail->replyTo('noreply@bodycool.com');
                        $sendEmail->subject('Confirmer votre compte Franchise BodyCool');
                        $sendEmail->context([
                            'user' => $user,
                        ]);
                        $sendEmail->htmlTemplate('emails/confirm-franchise.html.twig');
                    $mailer->send($sendEmail);
                } catch (\Exception $e) {
                    $errorNumber = $email . '_' . uniqid();
                    $logger->error('Erreur distribution du mail au franchisé', [
                        'errorNumber' => $errorNumber,
                        'emailFranchise' => $email,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);

                    $this->addFlash(
                        'exception',
                        'L\'email n\'a pas pu être envoyé au propriétaire. Log n° : ' . $errorNumber
                    );
                }
            }
        }
        
        return $this->render('franchise/add-franchise.html.twig', [
            'formFranchise' => $formFranchise->createView()
        ]);
    }



    /**
     * Page pour afficher les détails sur une franchise.
     * Le technicien pourra modifier les accès au permissions globables et activer ou désactiver la franchise.
     * Le franchisé pourra voir uniquement les détails de sa franchise en lecture seule.
     * 
     * @return Response
     */
    #[Route('/franchise-{id}', name: 'app_franchise_unique')]
    #[IsGranted('ROLE_FRANCHISE')]
    public function singleFranchise(
        $id,
        Request $request, 
        ManagerRegistry $doctrine, 
        MailerInterface $mailer, 
        LoggerInterface $logger
        ): Response
    {
        $em = $doctrine->getManager();

        /**
         * On récupère :
         * - la franchise correspondant à l'id passé en parametre
         * - le propriétaire de la franchise
         * - l'utilisateur connecté
         * - si l'utilisateur connecté est un admin
         * - l'état de la franchise (active ou pas)
         * - un tableau d'objet des permissions globales déjà acquis par la franchise
         */
        $repo = $doctrine->getRepository(Franchise::class);
        $franchise = $repo->findOneBy(['id' => $id]);
        // Si aucune franchise ne correspond à l'id on retourne une 404
        if (empty($franchise)) {
            throw $this->createNotFoundException('Cette franchise n\'existe pas.');
        }

        $userOwner = $franchise->getUserOwner();
        $userConnected = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $userConnected->getRoles());
        $franchiseIsActive = $franchise->isActive();
        $allGlobalPermissionsFranchise = $franchise->getGlobalPermissions()->toArray(); 
           

        // Si l'utilisateur connecté n'est pas le propriétaire de la franchise et n'est pas un admin on interdit l'accès à la page.
        if ($userConnected != $userOwner && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette page');
        }
        
        // Si la franchise est désactivé et que l'utilisateur connecté n'est pas un admin on interdit l'accès à la page.
        if ($franchise->isActive() === false && !$isAdmin) {
            throw $this->createAccessDeniedException('Cette franchise est désactivée');
        }
        

        $form = $this->createFormBuilder($franchise)
        ->add('globalPermissions', EntityType::class, [
            'class' => Permission::class,
            'choice_label' => 'id',
            'choice_value'=> 'id',
            'multiple' => true,
            'expanded' => true,
            'mapped' => true      
        ])
        ->add('active', CheckboxType::class, [
            'label'    => 'active',
            'required' => false,
            'mapped' => true,
        ])
        ->getForm();

        $form->handleRequest($request); 
        
        // Récupère un tableau des id des permissions dont la franchise déjà accès. 
        $idGlobalPermissions = $form['globalPermissions']->getViewData();
        
        // Variable qui va servir à préciser la modification apportée à la franchise..
        $changeFranchise = null;

        // On crée un tableau qui va contenir uniquement les noms des permissions déjà acquis.
        $valueGlobalPermissionsFranchise = [];
        foreach ($allGlobalPermissionsFranchise as $value) {
            array_push($valueGlobalPermissionsFranchise, $value->getName());
        }

        // On interdit la modification des données aux non admin.
        if($form->isSubmitted() && $form->isValid() && $isAdmin) {
            $data = $form->getData();
            $newGlobalPermissionsFranchise = $form->get('globalPermissions')->getData()->toArray();
            
            // On crée un tableau qui va contenir uniquement le nom des nouvelles permissions.
            $valueNewGlobalPermissionsFranchise = [];
            foreach ($newGlobalPermissionsFranchise as $value) {
                array_push($valueNewGlobalPermissionsFranchise, $value->getName());
            }
            
            /**
             * On compare les deux tableaux (anciennes permissions et nouvelles permissions).
             * - Si le premier tableau est inferieur au second tableau, cela veut dire qu'on a ajouté une nouvelle permission.
             * - Si le second tableau est supperieur au premier tableau, cela veut dire qu'on a supprimé une permission
             */
            if (count($valueGlobalPermissionsFranchise) < count($valueNewGlobalPermissionsFranchise)) {
                $arrayTmp = array_diff($valueNewGlobalPermissionsFranchise, $valueGlobalPermissionsFranchise);
                $changeFranchise = 'Ajout de la permission : ' . implode('', $arrayTmp);                
            } elseif (count($valueGlobalPermissionsFranchise) > count($valueNewGlobalPermissionsFranchise)) {
                $arrayTmp = array_diff($valueGlobalPermissionsFranchise, $valueNewGlobalPermissionsFranchise);
                $changeFranchise = 'Suppression de la permission : ' . implode('', $arrayTmp);
            }

            /**
             * On vérifie si la valeur de 'active' a été modifiée 
             */
            if ($franchiseIsActive != $data->isActive()) {
                if ($data->isActive() === true) {
                    $changeFranchise = 'La franchise a été activée';
                } else {
                    $changeFranchise = 'La franchise a été désactivée';
                }
            } 

            try {
                $em->persist($data);
                $em->flush();
                
                $this->addFlash(
                    'success',
                    $changeFranchise
                );
            } catch (\Exception $e) {
                $errorNumber = 'franchise-' . $id . '_' . uniqid();
                $logger->error('Erreur persistance des données', [
                    'errorNumber' => $errorNumber,
                    'idFranchise' => $id,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $this->addFlash(
                    'exception',
                    'Les modifications n\'ont pas pu être sauvegardées. Log n° : ' . $errorNumber
                );
            }
 
            // On envoi un email au franchisé pour le prévenir qu'il y a eu des modifications sur sa franchise.
            try {
                $sendEmail = new TemplatedEmail();
                    $sendEmail->from('BodyCool <noreply@bodycool.com>');
                    $sendEmail->to($userOwner->getEmail());
                    $sendEmail->replyTo('noreply@bodycool.com');
                    $sendEmail->subject('Votre franchise a été modifiée');
                    $sendEmail->context([
                        'changeFranchise' => $changeFranchise, 
                        'userOwner' => $userOwner
                    ]);
                    $sendEmail->htmlTemplate('emails/edit-franchise.html.twig');
                $mailer->send($sendEmail);

                $this->addFlash(
                    'success',
                    'Un email a été envoyé au franchisé'
                );
            } catch (\Exception $e) {
                $errorNumber = 'franchise-' . $id . '_' . uniqid();
                $logger->error('Erreur distribution du mail au franchisé', [
                    'errorNumber' => $errorNumber,
                    'idFranchise' => $id,
                    'emailFranchise' => $userOwner->getEmail(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $this->addFlash(
                    'exception',
                    'L\'email n\'a pas pu être envoyé au propriétaire. Log n° : ' . $errorNumber
                );
            }
        } 

        return $this->render('franchise/single-franchise.html.twig', [
            'franchise' => $franchise,
            'form' => $form->createView(),
            'userOwner' => $userOwner,
            'idGlobalPermissions' => $idGlobalPermissions,
        ]);
    }
}