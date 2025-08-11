<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Payment;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /** Somme des paiements d’une facture (centimes). */
    public function sumForInvoice(Invoice $invoice): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.amountCents), 0) as total')
            ->andWhere('p.invoice = :inv')
            ->setParameter('inv', $invoice);

        // DQL renvoie un string; caster en int
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
