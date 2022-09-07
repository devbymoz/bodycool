<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Franchise;
use App\Form\AddFranchiseType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


#[Route('/franchise')]
class FranchiseController extends AbstractController
{
    /**
     * Ajoute une nouvelle franchise en BDD avec son propriétaire et les permissions globales associées à la franchise.
     * 
     * @return Response
     */
    #[Route('/ajouter-franchise', name: 'app_ajouter_franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function addFranchise(
        Request $request, 
        ManagerRegistry $doctrine, 
        MailerInterface $mailer, 
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
              
            // Vérifie si l'email existe déja en BDD
            $email = $user->getEmail();
            $checkEmail = $repoUser->findBy(['email' => $email]);
            
            // Vérifie si le nom de la franchise existe déja en BDD
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
                // On attribue le role Franchise à l'utilisateur
                $user->setRoles(['ROLE_FRANCHISE']);
                
                $em->persist($data);
                $em->flush();

                // Envoi d'un email au franchisé pour qu'il confirme son compte
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

                $this->addFlash(
                    'success',
                    'La franchise a bien été créée'
                );
            }
        }
        
        return $this->render('franchise/add-franchise.html.twig', [
            'formFranchise' => $formFranchise->createView()
        ]);
    }
}