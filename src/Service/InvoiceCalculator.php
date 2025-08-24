<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class InvoiceCalculator
{
    /** Calcule totalNet/totalVat/totalGross en centimes à partir des lignes. */
    public function compute(Invoice $invoice): void
    {
        $totalNet = 0;
        $totalVat = 0;

        /** @var InvoiceLine $line */
        foreach ($invoice->getLines() as $line) {
            // quantités décimales possibles (ex: "1", "2.5")
            $qty   = BigDecimal::of((string)($line->getQuantity() ?? '1'));
            // prix unitaire en centimes
            $unit  = BigDecimal::of((string)($line->getUnitPriceCents() ?? 0));

            // total brut ligne en centimes
            $gross = $unit->multipliedBy($qty)->toScale(0, RoundingMode::HALF_UP)->toInt();

            $discount = max(0, (int) $line->getDiscountCents());
            $net      = max(0, $gross - $discount);

            // --- NORMALISATION DU TAUX ---
            // $percent peut être "19.6" (pourcentage) ou "0.196" (ratio)
            $raw = $line->getTaxRate()?->getPercent();
            $rate = ($raw !== null && $raw !== '')
                ? BigDecimal::of($raw)
                : BigDecimal::zero();

            // si ≥ 1, on considère que c'est un pourcentage → convertir en ratio
            if ($rate->compareTo(BigDecimal::one()) >= 0) {
                $rate = $rate->dividedBy(100, 6, RoundingMode::HALF_UP); // 19.6 -> 0.196000
            }

            $vat = BigDecimal::of($net)
                ->multipliedBy($rate)
                ->toScale(0, RoundingMode::HALF_UP)
                ->toInt();

            $totalNet += $net;
            $totalVat += $vat;
        }

        $invoice->setTotalNet($totalNet);
        $invoice->setTotalVat($totalVat);
        $invoice->setTotalGross($totalNet + $totalVat);
    }
}
