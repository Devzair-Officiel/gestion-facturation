<?php

/**
 * ligne d’une facture (désignation, quantité, prix unitaire HT, taxe, remise). Calculs des totaux faits côté service.
 */

namespace App\Entity;

use App\Repository\InvoiceLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceLineRepository::class)]
#[ORM\Index(name: 'idx_invoiceline_invoice', columns: ['invoice_id'])]
class InvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $designation;

    #[ORM\Column(type: 'decimal', precision: 18, scale: 6)]
    private string $quantity = '1'; // string decimal pour éviter les erreurs binaires

    #[ORM\Column(type: 'integer')]
    private int $unitPriceCents; // HT en centimes

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $discountCents = 0; // remise montant en centimes (option : gérer une remise % ailleurs)

    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private Invoice $invoice;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?TaxRate $taxRate = null;

    public function __construct(Invoice $invoice, string $designation, int $unitPriceCents)
    {
        $this->invoice = $invoice;
        $this->designation = $designation;
        $this->unitPriceCents = $unitPriceCents;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): string
    {
        return $this->designation;
    }

    public function setDesignation($designation): static
    {
        $this->designation = $designation;

        return $this;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function setUnitPriceCents($unitPriceCents): static
    {
        $this->unitPriceCents = $unitPriceCents;

        return $this;
    }

    public function getDiscountCents(): int
    {
        return $this->discountCents;
    }

    public function setDiscountCents($discountCents): static
    {
        $this->discountCents = $discountCents;

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

    public function getTaxRate(): ?TaxRate
    {
        return $this->taxRate;
    }

    public function setTaxRate(?TaxRate $taxRate): static
    {
        $this->taxRate = $taxRate;

        return $this;
    }
}
