<?php

/**
 * Taux de taxe/TVA applicable aux lignes de facture. Stocké en pourcentage décimal (ex : "0.2000" pour 20%).
 */

namespace App\Entity;

use App\Repository\TaxRateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaxRateRepository::class)]
class TaxRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 40)]
    private string $title; // ex: "TVA 20%"

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $percent = null;

    #[ORM\Column(length: 2, options: ['default' => 'FR'])]
    private string $country = 'FR';

    #[ORM\Column]
    private bool $active = true;


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

    /** Pour l’admin: 19.60 = 19,60 % */
    public function getPercent(): ?float
    {
        return $this->percent !== null ? (float)$this->percent : null;
    }
    public function setPercent(?float $percent): static
    {
        $this->percent = $percent !== null ? number_format($percent, 2, '.', '') : null;
        return $this;
    }

    /** Pour les calculs: 0.196 = 19,6 % */
    public function getRatio(): float
    {
        $p = $this->getPercent();
        return $p ? $p / 100.0 : 0.0;
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

    public function __toString(): string
    {
        return $this->title ?? 'taxe';
    }
}
