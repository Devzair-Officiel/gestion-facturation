<?php

/**
 * encaissement rattaché à une Invoice. Un service PaymentAllocator met à jour le statut de la facture (partiellement payée/paid).
 */

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Index(name: 'idx_payment_invoice', columns: ['invoice_id'])]
#[ORM\Index(name: 'idx_payment_reference', columns: ['reference'])]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', unique: true)]
    private ?int $id = null;

    
    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $paidAt;

    #[ORM\Column(length: 24)]
    private string $method; // card, transfer, cash…

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private Invoice $invoice;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents): static
    {
        $this->amountCents = $amountCents;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function __toString(): string
    {
        // Facture, numéro, client
        $invoice = $this->getInvoice();
        $number  = $invoice?->getNumber() ?: '—';
        $client  = $invoice?->getCustomerName() ?: 'Client';

        // Société émettrice (encaisseur)
        $companyName = $this->getCompany()?->getLegalName() ?: 'Société';

        // Montant + devise
        $amount   = $this->getAmountCents() !== null ? number_format($this->getAmountCents() / 100, 2, ',', ' ') : '0,00';
        $currency = $invoice?->getCurrency() ?: 'EUR';

        // Date d’encaissement
        $date = $this->getPaidAt()?->format('d/m/Y') ?: '—';

        // Ex: "Facture 2025-000123 — ACME Corp — 1 234,56 EUR (DevZair SARL) [24/08/2025]"
        return sprintf('Facture %s — %s — %s %s (%s) [%s]', $number, $client, $amount, $currency, $companyName, $date);
    }
}
