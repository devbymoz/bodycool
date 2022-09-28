<?php

namespace App\Repository;

use App\Entity\Franchise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Franchise>
 *
 * @method Franchise|null find($id, $lockMode = null, $lockVersion = null)
 * @method Franchise|null findOneBy(array $criteria, array $orderBy = null)
 * @method Franchise[]    findAll()
 * @method Franchise[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FranchiseRepository extends ServiceEntityRepository
{
    // Sert à stocker le nombre d'élement renvoyer par une query.
    private array $nbrElement;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Franchise::class);
    }

    public function add(Franchise $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Franchise $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * Récupère les franchises en fonction des critères suivants :
     *
     * @param [bool] $stateActive : l'état de la franchise.
     * @param [id] $paramSearchId : rechercher par id de franchise.
     * @param [string] $paramSearchName : recherche par nom de franchise.
     * @param [int] $nbPerPage : nombre d'élément par page.
     * @param [int] $numpage : numéro de page du parametre page.
     * @return array : le resultat de la requete.
     */
    public function findFranchisesFilter(
        $stateActive, 
        $paramSearchId, 
        $paramSearchName,
        $nbPerPage,
        $numpage
        )
    {
        $query = $this
            ->createQueryBuilder('f')
            ->orderBy('f.id', 'ASC');

        // Si nous avons une selection par état de franchise.
        if (!empty($stateActive) || $stateActive != null) {
            $query = $query
                ->andWhere('f.active = :a')
                ->setParameter('a', $stateActive);
        }

        // Si nous avons un recherche par id de franchise.
        if (!empty($paramSearchId)) {
            $query = $query
                ->andWhere('f.id LIKE :sid')
                ->setParameter('sid', "%$paramSearchId%");
        }

        // Si nous avons un recherche par nom de franchise.
        if (!empty($paramSearchName)) {
            $query = $query
                ->andWhere('f.name LIKE :sname')
                ->setParameter('sname', "%$paramSearchName%");
        }

        // On renvoi la query sans la limiation, pour pouvoir faire notre pagination et l'envoyer au PaginationService.
        $this->setNbrElement($query->getQuery()->getResult());
        
        // On limite le nombre d'élément à afficher.
        $query = $query
            ->setMaxResults($nbPerPage)
            ->setFirstResult($nbPerPage * ($numpage - 1));

        return $query->getQuery()->getResult();
    }


    public function getNbrElement(): array
    {
        return $this->nbrElement;
    }

    public function setNbrElement(?array $nbrElement): self
    {
        $this->nbrElement = $nbrElement;

        return $this;
    }


}















