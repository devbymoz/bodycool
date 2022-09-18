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
    private int $nbrElement;

    /**
     * Permet d'avoir une pagination.
     * $numpage : correspond au paramètre du numéro de page de l'url.
     * $nbPerPage : le nombre d'élément à afficher par page.
     * $repo : le Repository de l'entité pour laquelle nous devons pagginer, ex = $doctrine->getRepository(Franchise::class);
     * $criteriaRequest : permet d'ajouter des critères de séléction à la requete.
     * 
     * @param int $numpage
     * @param int $nbPerPage
     * @param [Repository] $repo
     * @return void
     */
    public function myPagination($numpage, $nbPerPage, $repo, $criteriaRequest)
    {
        // On récupère tous les éléments de la BDD sans la limitation de page.
        $allElements = $repo->findBy(
            $criteriaRequest,
            ['id' => 'ASC'], 
        );

        $this->nbrElement = count($allElements);

        // On calcule le nombre de page total que nous aurons.
        $this->nbPage = intval(ceil(count($allElements) / $nbPerPage));

        // On crée un tableau qui va contenir les pages que nous devons afficher.
        $this->pagination = range($numpage, $this->nbPage);

        // On trie le tableau pour ne garder que 4 pages.
        // On affiche les 4 dernières pages.
        if($this->nbPage > 3 && $numpage > ($this->nbPage - 3)) {
            $this->pagination = range(3, $this->nbPage);
        } elseif($this->nbPage > 4) {
            // On affiche les 3 pages courantes et la dernière page.
            array_splice($this->pagination, 3, -1);
        } elseif($this->nbPage <= 3) {
            // On affiche toutes les pages.
            $this->pagination = range(1, $this->nbPage);
        }


    }



    public function getNbPage()
    {
        return $this->nbPage;
    }

    public function getPagination()
    {
        return $this->pagination;
    }

    public function getNbrElement()
    {
        return $this->nbrElement;
    }


}