<?php

namespace App\Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * GÈRE LA PAGINATION.
 * 
 */
class PaginationService extends AbstractController
{

    private int $nbPage;
    private array $pagination;
    private bool $dot = false; // Pour avoir les 3 petits points dans la pagination.

    /**
     * Permet d'avoir une pagination. Calcule le nombre de page en prenant le nombre d'élément renvoyé par la query avant la limitation.
     * 
     * @param int $numpage : correspond au paramètre du numéro de page de l'url.
     * @param int $nbPerPage : le nombre d'élément à afficher par page.
     * @param int $nbrElement : le nombre d'élément de la query sans la limitation. 
     * @return array : le tableau des pages à afficher.
     */
    public function myPagination($numpage, $nbPerPage, $nbrElement)
    {
        // On calcule le nombre de page total que nous aurons.
        $this->nbPage = intval(ceil($nbrElement / $nbPerPage));

        // On crée un tableau qui va contenir le nombre de  pages que nous devrons afficher.
        $this->pagination = [];

        // Conditions pour remplir le tableau de pagination.
        if ($this->nbPage <= 4) {
            // Si on a moins de 4 pages, on les affiches toutes
            $this->pagination = range(1, $this->nbPage);
        } elseif ($this->nbPage > 4 && $this->nbPage - $numpage < 4) {
            // Si on à plus de 4 pages et que le nombre de page fait partie des 4 dernières pages, on affiche les 4 dernières pages.
            $this->pagination = range($this->nbPage-3, $this->nbPage);
        } elseif ($this->nbPage > 4) {
            // Si on a plus de 4 pages, on affiche les 3 pages en partant du numpage et la dernière page du tableau.
            // On coupe le tableau pour garder les 3 pages + la derniere.
            $this->pagination = range($numpage, $this->nbPage);
            array_splice($this->pagination, 3, -1);
            $this->dot = true;
        }

        return $this->pagination;
    }

    
    public function getNbPage()
    {
        return $this->nbPage;
    }

    public function getPagination()
    {
        return $this->pagination;
    }

    public function getDot()
    {
        return $this->dot;
    }

    
}