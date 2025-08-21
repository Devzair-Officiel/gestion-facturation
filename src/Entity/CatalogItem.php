<?php

namespace App\Entity;

use App\Entity\TaxRate;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_item')]
class CatalogItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column(type: 'integer')]
    private int $defaultUnitPriceCents = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?TaxRate $defaultTaxRate = null;

    #[ORM\Column(length: 32, options: ['default' => 'forfait'])]
    private string $unit = 'forfait'; // ex: 'forfait','pièce','heure'

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    // getters/setters…
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $n): self
    {
        $this->name = $n;
        return $this;
    }
    public function getSku(): ?string
    {
        return $this->sku;
    }
    public function setSku(?string $s): self
    {
        $this->sku = $s;
        return $this;
    }
    public function getDefaultUnitPriceCents(): int
    {
        return $this->defaultUnitPriceCents;
    }
    public function setDefaultUnitPriceCents(int $c): self
    {
        $this->defaultUnitPriceCents = $c;
        return $this;
    }
    public function getDefaultTaxRate(): ?TaxRate
    {
        return $this->defaultTaxRate;
    }
    public function setDefaultTaxRate(?TaxRate $t): self
    {
        $this->defaultTaxRate = $t;
        return $this;
    }
    public function getUnit(): string
    {
        return $this->unit;
    }
    public function setUnit(string $u): self
    {
        $this->unit = $u;
        return $this;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function setIsActive(bool $a): self
    {
        $this->isActive = $a;
        return $this;
    }
}
