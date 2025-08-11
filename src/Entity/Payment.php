<?php

/**
 * encaissement rattaché à une Invoice. Un service PaymentAllocator met à jour le statut de la facture (partiellement payée/paid).
 */

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Index(name: 'idx_payment_invoice', columns: ['invoice_id'])]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'uuid', unique: true)]
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

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Invoice $invoice;


    public function __construct(Company $company, Invoice $invoice, int $amountCents, \DateTimeImmutable $paidAt, string $method = 'transfer')
    {
        $this->company = $company;
        $this->invoice = $invoice;
        $this->amountCents = $amountCents;
        $this->paidAt = $paidAt;
        $this->method = $method;
    }

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
}
