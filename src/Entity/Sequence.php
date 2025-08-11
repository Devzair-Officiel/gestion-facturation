<?php

/**
 *  tient le compteur par company + année pour générer les numéros de factures (2025-000123). Utilisé par un service Sequencer.
 */

namespace App\Entity;

use App\Repository\SequenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SequenceRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_seq_company_year', columns: ['company_id', 'year'])]
class Sequence
{
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $year;

    #[ORM\Column(type: 'integer')]
    private int $lastNumber = 0;

    public function __construct(Company $company, int $year)
    {
        $this->company = $company;
        $this->year = $year;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear($year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getLastNumber(): int
    {
        return $this->lastNumber;
    }

    public function setLastNumber($lastNumber): static
    {
        $this->lastNumber = $lastNumber;

        return $this;
    }
}
