<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Sequence;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Sequence>
 */
class SequenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sequence::class);
    }

    /**
     * Incrémente et retourne le prochain numéro pour (company, year).
     * Verrou pessimiste + transaction pour éviter les collisions concurrentes.
     */
    public function reserveNextNumber(Company $company, int $year): int
    {
        $em = $this->getEntityManager();

        return $em->wrapInTransaction(function () use ($em, $company, $year) {
            /** @var Sequence|null $seq */
            $seq = $this->findOneBy(['company' => $company, 'year' => $year]);

            if ($seq) {
                $em->lock($seq, LockMode::PESSIMISTIC_WRITE);
            } else {
                $seq = new Sequence($company, $year);
                $em->persist($seq);
            }

            $next = $seq->getLastNumber() + 1;
            $seq->setLastNumber($next);

            // flush géré par wrapInTransaction
            return $next;
        });
    }
}
