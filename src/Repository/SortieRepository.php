<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 *
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }*/

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findSortiesBySearch($searchTerm, $dateDebut, $dateFin, $selectedCampusId, $user)
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.campus_sorties', 'c')
            ->andWhere('s.nom LIKE :searchTerm OR c.nom LIKE :searchTerm')
            ->setParameter('searchTerm', "%$searchTerm%");

        if ($dateDebut) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($dateDebut));
        }

        if ($dateFin) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', new \DateTime($dateFin));
        }

        if ($selectedCampusId) {
            $qb->andWhere('c.id = :selectedCampusId')
                ->setParameter('selectedCampusId', $selectedCampusId);
        }

        if ($user) {
            $qb->andWhere('s.organisateur = :userId')
                ->setParameter('userId', $user->getId());
        }

        return $qb->getQuery()->getResult();
    }

    public function findSortiesBySearchWithFilters(
        $searchTerm,
        $dateDebut,
        $dateFin,
        $selectedCampusId,
        $organisateur,
        $inscrit,
        $nonInscrit,
        $passees,
        $user
    ) {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.campus_sorties', 'c')
            ->andWhere('s.nom LIKE :searchTerm OR c.nom LIKE :searchTerm')
            ->setParameter('searchTerm', "%$searchTerm%");

        if ($dateDebut) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($dateDebut));
        }
        if ($dateFin) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', new \DateTime($dateFin));
        }

        if ($selectedCampusId) {
            $qb->andWhere('c.id = :selectedCampusId')
                ->setParameter('selectedCampusId', $selectedCampusId);
        }


        if ($organisateur) {
            if ($user) {
                $qb->andWhere('s.organisateur = :userId')
                    ->setParameter('userId', $user->getId());
            }
        }

        if ($inscrit) {

            $qb->andWhere(':user MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if ($nonInscrit) {
            $qb->andWhere(':user NOT MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if ($passees) {
            $qb->andWhere('s.dateHeureDebut < :now')
                ->setParameter('now', new \DateTime());
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Sortie[] Returns an array of Sortie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sortie
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
