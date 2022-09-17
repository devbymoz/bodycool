<?php 

namespace App\Controller\Franchise;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * GĒRE LA RECHERCHE DES FRANCHISES
 *
 */
class SearchFranchiseController extends AbstractController 
{
    
    /**
     * Permet de trier les franchises par état (activé ou désactivé).
     * 
     */
    #[Route('/rechercher-{state}', name: 'app_rechercher-franchise')]
    #[IsGranted('ROLE_ADMIN')]
    public function showFranchiseByState(
        $state, 
        ManagerRegistry $doctrine, 
        LoggerInterface $logger,
        MailerInterface $mailer, 
        )
	{
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Franchise::class);

        // On récupère la franchise correspondant à l'id en paramètre.
        $franchise = $repo->findBy(['isActive' => $state]);



        return $this->json([
            'code' => 200, 
            'message' => 'franchise récupérée avec success',
            'newStateFranchise' => $newStateFranchise,
            'franchiseName' => $franchise->getName()
        ], 200);
    }




}