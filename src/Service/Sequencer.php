<?php

/**
 * But : générer un numéro unique par Company + année (YYYY-000001) avec verrouillage transactionnel.
 */

namespace App\Service;

use App\Entity\Company;
use App\Repository\SequenceRepository;

final class Sequencer
{
    public function __construct(private readonly SequenceRepository $sequences) {}

    /** Retourne un numéro ex: "2025-000123". */
    public function nextFor(Company $company, ?\DateTimeImmutable $at = null): string
    {
        $at ??= new \DateTimeImmutable();
        $year = (int) $at->format('Y');

        $next = $this->sequences->reserveNextNumber($company, $year);

        return sprintf('%d-%06d', $year, $next);
    }
}
