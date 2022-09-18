<?php

namespace App\Service;

use Psr\Log\LoggerInterface;


/**
 * Gère les logs
 * 
 */
class LoggerService 
{

    private string $errorNumber;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log le numéro de ligne et le fichier où se trouve l'erreur.
     * 
     * Retourne le numéro de l'erreur qui a été généré.
     *
     * @param $exception (type d'exception)
     * @param [type] $message (message de l'exception)
     * @return string (numéro d'erreur généré)
     */
    public function logGeneric($exception, $message)
    {
        $this->errorNumber = uniqid();
        $this->logger->error($message, [
            'errorNumber' => $this->errorNumber,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return $this->errorNumber;
    }


    /**
     * Get the value of errorNumber
     */ 
    public function getErrorNumber()
    {
        return $this->errorNumber;
    }
}