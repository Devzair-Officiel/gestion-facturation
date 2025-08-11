<?php

/**
 * article/service vendable. Contient un prix unitaire HT par défaut (en centimes) et un defaultTax
 */

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Index(name: 'idx_product_company', columns: ['company_id'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $title;

    #[ORM\Column(type: 'integer')]
    private int $unitPriceCents; // prix HT par défaut

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'boolean')]
    private bool $archived = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?TaxRate $defaultTax = null;

    public function __construct(Company $company, string $title, int $unitPriceCents)
    {
        $this->company = $company;
        $this->title = $title;
        $this->unitPriceCents = $unitPriceCents;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle($title): static
    {
        $this->title = $title;

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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency($currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived($archived): static
    {
        $this->archived = $archived;

        return $this;
    }
}
