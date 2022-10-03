<?php

namespace App\Repository;

use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Structure>
 *
 * @method Structure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Structure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Structure[]    findAll()
 * @method Structure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StructureRepository extends ServiceEntityRepository
{

    // Sert à stocker le nombre d'élement renvoyer par une query.
    private $nbrElement;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Structure::class);
    }

    public function add(Structure $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Structure $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * Récupère les structures en fonction des critères suivants :
     *
     * @param [bool] $stateActive : l'état de la structure.
     * @param [id] $paramSearchId : rechercher par id de structure.
     * @param [string] $paramSearchName : recherche par nom de structure.
     * @param [int] $nbPerPage : nombre d'élément par page.
     * @param [int] $numpage : numéro de page du parametre page.
     * @return array : le resultat de la requete.
     */
    public function findElementFilter(
        $stateActive,
        $paramSearchId,
        $paramSearchName,
        $nbPerPage,
        $numpage
    ) {
        $query = $this
            ->createQueryBuilder('e')
            ->orderBy('e.id', 'ASC');

        // Si nous avons une selection par état (active/inactive).
        if (!empty($stateActive) || $stateActive != null) {
            $query = $query
                ->andWhere('e.active = :a')
                ->setParameter('a', $stateActive);
        }

        // Si nous avons un recherche par id d'élement.
        if (!empty($paramSearchId)) {
            $query = $query
                ->andWhere('e.id = :sid')
                ->setParameter('sid', $paramSearchId);
        }

        // Si nous avons un recherche par nom d'élément.
        if (!empty($paramSearchName)) {
            $query = $query
                ->andWhere('e.name LIKE :sname')
                ->setParameter('sname', "%$paramSearchName%");
        }

        // On renvoi la query sans la limiation, pour pouvoir faire notre pagination et l'envoyer au PaginationService.
        $this->setNbrElement($query->getQuery()->getArrayResult());
        
        // On limite le nombre d'élément à afficher.
        $query = $query
            ->setMaxResults($nbPerPage)
            ->setFirstResult($nbPerPage * ($numpage - 1));

        return $query->getQuery()->getResult();
    }


    public function getNbrElement()
    {
        return $this->nbrElement;
    }

    public function setNbrElement($nbrElement): self
    {
        $this->nbrElement = $nbrElement;

        return $this;
    }
}
