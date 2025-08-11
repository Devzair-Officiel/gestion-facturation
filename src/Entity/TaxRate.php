<?php

/**
 * Taux de taxe/TVA applicable aux lignes de facture. Stocké en pourcentage décimal (ex : "0.2000" pour 20%).
 */

namespace App\Entity;

use App\Repository\TaxRateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaxRateRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_tax_company_title', columns: ['company_id', 'title'])]

class TaxRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 40)]
    private string $title; // ex: "TVA 20%"

    #[ORM\Column(type: 'decimal', precision: 7, scale: 4)]
    private string $percent; // "0.2000" = 20%

    #[ORM\Column(length: 2, options: ['default' => 'FR'])]
    private string $country = 'FR';

    #[ORM\Column]
    private bool $active = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    public function __construct(Company $company, string $title, string $percent)
    {
        $this->company = $company;
        $this->title = $title;
        $this->percent = $percent;
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

    public function getPercent(): string
    {
        return $this->percent;
    }

    public function setPercent($percent): static
    {
        $this->percent = $percent;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry($country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive($active): static
    {
        $this->active = $active;

        return $this;
    }
}
