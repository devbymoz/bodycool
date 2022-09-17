<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * VÉRIFICATION SUPPLÉMENTAIRE POUR LA CONNEXION DE L'UTILISATEUR
 * 
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        $request = Request::createFromGlobals();
        $email = $request->request->get('_username');
        $password = $request->request->get('_password');

        if (!$user instanceof AppUser) {
            return;
        }

        /**
         * Condition pour que l'utilisateur puisse se connecter :
         * - Entrez un mot de passe valide.
         * - Entrez un email valide.
         * - Le compte doit etre activé.
         */
        if($password === '' ) {
            throw new CustomUserMessageAccountStatusException('Vous devez entrer un mot de passe');
        } elseif ($email === '') {
            throw new CustomUserMessageAccountStatusException('Vous devez entrer un email');
        } elseif(strlen($password) < 8) {
            throw new CustomUserMessageAccountStatusException('Mot de passe trop court');
        } elseif($user->isActive() === false) {
            // Si l'utilisateur est désactivé on lui affiche un message
            throw new CustomUserMessageAccountStatusException('Votre compte est désactivé');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
            return;
    }
}