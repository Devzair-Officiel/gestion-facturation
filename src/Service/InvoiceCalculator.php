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
            $qty   = BigDecimal::of($line->getQuantity());           // "1.250000"
            $unit  = BigDecimal::of($line->getUnitPriceCents());     // 12345 (centimes)
            $gross = $unit->multipliedBy($qty)->toScale(0, RoundingMode::HALF_UP)->toInt();

            $discount = max(0, $line->getDiscountCents());
            $netLine  = max(0, $gross - $discount);

            $rate = $line->getTaxRate()?->getPercent() ?? '0';
            $vatLine = BigDecimal::of($netLine)
                ->multipliedBy($rate)
                ->toScale(0, RoundingMode::HALF_UP)
                ->toInt();

            $totalNet += $netLine;
            $totalVat += $vatLine;
        }

        $invoice->setTotalNet($totalNet);
        $invoice->setTotalVat($totalVat);
        $invoice->setTotalGross($totalNet + $totalVat);
    }
}
