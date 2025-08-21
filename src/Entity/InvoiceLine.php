<?php

/**
 * ligne d’une facture (désignation, quantité, prix unitaire HT, taxe, remise). Calculs des totaux faits côté service.
 */

namespace App\Entity;

use App\Repository\InvoiceLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceLineRepository::class)]
class InvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $designation;

    #[ORM\Column(type: 'decimal')]
    #[Assert\Positive]
    private string $quantity = '1'; // string decimal pour éviter les erreurs binaires

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private int $unitPriceCents; // HT en centimes

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $discountCents = 0; // remise montant en centimes (option : gérer une remise % ailleurs)

    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?TaxRate $taxRate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?CatalogItem $item = null;

    public function __construct()
    {
        $this->quantity = '1.000';
        $this->discountCents = 0;
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

    public function getItem(): ?CatalogItem
    {
        return $this->item;
    }
    public function setItem(?CatalogItem $i): self
    {
        $this->item = $i;
        return $this;
    }
    
}
