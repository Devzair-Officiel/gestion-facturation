<?php

namespace App\Service;

use App\Entity\Payment;
use App\Enum\InvoiceStatus;
use Doctrine\ORM\EntityManagerInterface;

final class PaymentAllocator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function allocate(Payment $payment): void
    {
        $invoice = $payment->getInvoice();

        // Sécurité : le Payment hérite toujours de la company de la facture
        if ($invoice && $invoice->getCompany() !== null) {
            $payment->setCompany($invoice->getCompany());
        }

        // (Optionnel) si tu stockes la devise/amount d’origine, fais-le ici

        // Recalcul du statut de la facture
        $total = $invoice->getTotalGross();
        $paid  = 0;
        foreach ($invoice->getPayments() as $p) {
            $paid += (int) $p->getAmountCents();
        }

        if ($paid <= 0) {
            // Laisse ISSUED/SENT tel quel
        } elseif ($paid < $total) {
            $invoice->setStatus(InvoiceStatus::PARTIALLY_PAID);
        } else { // >=
            $invoice->setStatus(InvoiceStatus::PAID);
        }

        $this->em->flush(); // flush court, on ne modifie que Invoice & Payment
    }
}
