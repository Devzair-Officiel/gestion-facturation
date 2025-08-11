<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Enum\InvoiceStatus;
use App\Repository\PaymentRepository;

final class PaymentAllocator
{
    public function __construct(private readonly PaymentRepository $payments) {}

    /**
     * Met à jour le statut de la facture selon le total payé.
     * Retourne le montant total payé (centimes).
     */
    public function updateStatus(Invoice $invoice): int
    {
        $sum   = $this->payments->sumForInvoice($invoice);
        $total = $invoice->getTotalGross();

        if ($total > 0 && $sum >= $total) {
            $invoice->setStatus(InvoiceStatus::PAID);
        } elseif ($sum > 0 && $sum < $total) {
            $invoice->setStatus(InvoiceStatus::PARTIALLY_PAID);
        } else {
            $today = new \DateTimeImmutable('today');
            $isEmitted = \in_array($invoice->getStatus(), [InvoiceStatus::ISSUED, InvoiceStatus::SENT], true);
            if ($isEmitted && $invoice->getDueDate() < $today) {
                $invoice->setStatus(InvoiceStatus::OVERDUE);
            }
        }

        return $sum;
    }
}
