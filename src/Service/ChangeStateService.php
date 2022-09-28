<?php

namespace App\Service;

use App\Service\LoggerService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;


/**
 * Gère le changement d'état (activé / Désactivé)
 * 
 */
class ChangeStateService 
{

    private $logger;
    private $newStateObject;
    private $em;

    public function __construct(LoggerService $loggerService, ManagerRegistry $doctrine, )
    {
        $this->logger = $loggerService;
        $this->em = $doctrine->getManager();;
    }

    /**
     * Permet de changer l'état d'un objet (activé ou désactivé);
     * 
     * Prend l'objet à modifier, cet objet doit contenir la propriété active.
     *
     * @param [array] $object : la query de l'élément à changer.
     * @return void
     */
    public function changeStateObject($object) {
        // On récupère l'état de l'objet à changer (activée ou désactivée).
        $stateFranchise = $object->isActive();

        // On inverse l'état de l'objet.
        $object->setActive(!$stateFranchise);

        // On sauvegarde le nouvel état dans une variable
        $this->newStateObject = $object->isActive();

        // On sauvegarder le nouvel état de l'objet en BDD
        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->logGeneric($e, 'Erreur persistance des données');

            return New JsonResponse([
                'code' => 500,
                'message' => 'Erreur de persistance des données',
                'franchiseName' => $object->getName(),
                'errorNumber' => $this->logger->getErrorNumber()
            ], 500);
        }
    }



    public function getNewStateObject()
    {
        return $this->newStateObject;
    }


}