<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


/**
 * Tout ce qui concerne une franchise
 * - L'ajout d'une franchise
 */
#[Route('/franchise')]
class FranchiseController extends AbstractController
{
    /**
     * Ajoute un nouveau franchisé en BDD
     * Une franchise lui est associée
     * Les permissions globales sont associées à la franchise
     * @return Response
     */
    #[Route('/ajouter-franchise', name: 'app_ajouter.franchise')]
    ##[IsGranted('ROLE_FRANCHISE')]
    public function addFranchise(
        Request $request, 
        ManagerRegistry $doctrine, 
        MailerInterface $mailer, 
        ): Response
    {
        
        $em = $doctrine->getManager();
        $user = new User();
        $repo = $doctrine->getRepository(User::class);

        $formUser = $this->createForm(UserType::class, $user);
        $formUser->handleRequest($request);

        if($formUser->isSubmitted() && $formUser->isValid()) {
            $data = $formUser->getData();
            $email = $data->getEmail();
            
            // Vérifie si un email existe déja en BDD
            $checkEmail = $repo->findBy(['email' => $email]);

            if($checkEmail != []) {
                $this->addFlash(
                    'notice',
                    'Cet email existe déjà'
                );
            } else {
                // On défini le role Franchise à l'utilisateur
                $user->setRoles(['ROLE_FRANCHISE']);
                        
                $em->persist($data);
                $em->flush();

                //Envoi d'un email au franchisé pour confirmer son compte
                $sendEmail = new TemplatedEmail();
                    $sendEmail->from('noreply@bodycool.com');
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
            'formUser' => $formUser->createView()
        ]);
    }
}